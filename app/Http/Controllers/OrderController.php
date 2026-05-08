<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with('items');

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } else {
            $sessionId = $request->header('X-Session-ID');
            $query->where('session_id', $sessionId);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = Order::query()->with('items');

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } else {
            $sessionId = $request->header('X-Session-ID');
            $query->where('session_id', $sessionId);
        }

        $order = $query->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json(['data' => $order]);
    }

    public function getCheckoutSession(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $stripeKey = config('stripe.secret');
        $sessionId = $request->header('X-Session-ID') ?? Str::random(40);
        $userId = $request->user()?->id;

        // Create pending order first (always)
        $order = Order::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'status' => Order::STATUS_PENDING,
            'total_amount' => $cart->subtotal,
            'currency' => 'usd',
            'items_count' => $cart->item_count,
        ]);

        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Unknown Product',
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ]);
        }

        // If Stripe not configured, return mock response for testing
        if (!$stripeKey || $stripeKey === 'sk_test_your_secret_key') {
            return response()->json([
                'data' => [
                    'checkout_url' => config('app.url') . '/checkout/mock?order_id=' . $order->id,
                    'session_id' => 'cs_test_mock_' . $order->id,
                    'order_id' => $order->id,
                    'warning' => 'Stripe not configured - using mock mode',
                ],
            ]);
        }

        // Real Stripe checkout
        $lineItems = $cart->items->map(function ($item) {
            return [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product_name ?? $item->product->name,
                        'images' => $item->product?->image_url ? [$item->product->image_url] : [],
                    ],
                    'unit_amount' => (int) (bcmul($item->price, 100, 0)),
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();

        try {
            $stripe = new \Stripe\StripeClient($stripeKey);

            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'line_items' => $lineItems,
                'success_url' => config('app.url') . '/orders?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . '/cart',
                'metadata' => [
                    'user_id' => (string) $userId,
                    'session_id' => $sessionId,
                    'cart_id' => (string) $cart->id,
                ],
                'shipping_address_collection' => [
                    'allowed_countries' => ['US', 'GB', 'DE', 'FR', 'NL', 'AU', 'CA', 'ID'],
                ],
            ]);

            $order->update(['stripe_session_id' => $session->id]);

            return response()->json([
                'data' => [
                    'checkout_url' => $session->url,
                    'session_id' => $session->id,
                    'order_id' => $order->id,
                ],
            ]);
        } catch (\Exception $e) {
            $order->update(['stripe_session_id' => 'cs_err_' . Str::random(10)]);
            return response()->json([
                'message' => 'Failed to create checkout session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getCart(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::with('items')->where('user_id', $request->user()->id)->first()
                ?? Cart::with('items')->create(['user_id' => $request->user()->id]);
        }

        $sessionId = $request->header('X-Session-ID') ?? $request->session()->get('_token');
        return Cart::with('items')->where('session_id', $sessionId)->first()
            ?? Cart::with('items')->create(['session_id' => $sessionId]);
    }
}
