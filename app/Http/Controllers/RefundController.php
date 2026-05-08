<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    public function create(Request $request, int $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Check already refunded BEFORE checking unpaid
        if ($order->status === Order::STATUS_REFUNDED) {
            return response()->json(['message' => 'Order already refunded'], 400);
        }

        if (!$order->isPaid()) {
            return response()->json(['message' => 'Only paid orders can be refunded'], 400);
        }

        $stripeKey = config('stripe.secret');

        if (!$stripeKey || $stripeKey === 'sk_test_your_secret_key') {
            // For testing without Stripe configured, just mark as refunded
            $order->update(['status' => Order::STATUS_REFUNDED]);
            return response()->json([
                'message' => 'Refund processed (Stripe not configured - test mode)',
                'data' => [
                    'refund_id' => 're_test_mock',
                    'amount' => $order->total_amount,
                    'status' => 'succeeded',
                ],
            ]);
        }

        if (!$order->stripe_payment_intent_id) {
            return response()->json(['message' => 'No payment intent found for this order'], 400);
        }

        try {
            $stripe = new \Stripe\StripeClient($stripeKey);

            $refund = $stripe->refunds->create([
                'payment_intent' => $order->stripe_payment_intent_id,
            ]);

            // Restore stock
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $item->product()->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => Order::STATUS_REFUNDED]);

            Log::info('Refund processed', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
            ]);

            return response()->json([
                'message' => 'Refund processed successfully',
                'data' => [
                    'refund_id' => $refund->id,
                    'amount' => $refund->amount / 100,
                    'status' => $refund->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Refund failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
