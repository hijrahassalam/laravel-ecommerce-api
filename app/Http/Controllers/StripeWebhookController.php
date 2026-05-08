<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        if (!$webhookSecret || $webhookSecret === 'whsec_your_webhook_secret') {
            Log::warning('Stripe webhook received but secret not configured');
            return response()->json(['message' => 'Webhook secret not configured'], 400);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'checkout.session.expired' => $this->handleCheckoutExpired($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => Log::info('Unhandled webhook type: ' . $event->type),
        };

        return response()->json(['received' => true]);
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $order = Order::where('stripe_session_id', $session->id)->first();

        if (!$order) {
            Log::error('Order not found for stripe session: ' . $session->id);
            return;
        }

        if ($order->status === Order::STATUS_PAID) {
            Log::info('Order already paid: ' . $order->id);
            return;
        }

        // Update order status
        $order->update([
            'status' => Order::STATUS_PAID,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'paid_at' => now(),
            'shipping_name' => $session->customer_details?->name ?? null,
            'shipping_email' => $session->customer_details?->email ?? null,
            'shipping_address' => $session->shipping_details?->address
                ? json_encode($session->shipping_details->address)
                : null,
        ]);

        // Clear cart by session
        if ($order->session_id) {
            Cart::where('session_id', $order->session_id)->delete();
        }

        // Decrement stock
        foreach ($order->items as $item) {
            if ($item->product_id) {
                $item->product()->decrement('stock', $item->quantity);
            }
        }

        Log::info('Order marked as paid', ['order_id' => $order->id]);
    }

    private function handleCheckoutExpired(object $session): void
    {
        $order = Order::where('stripe_session_id', $session->id)->first();

        if ($order && $order->status === Order::STATUS_PENDING) {
            $order->update(['status' => Order::STATUS_FAILED]);
            Log::info('Order marked as expired', ['order_id' => $order->id]);
        }
    }

    private function handlePaymentFailed(object $paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($order && $order->status === Order::STATUS_PENDING) {
            $order->update(['status' => Order::STATUS_FAILED]);
            Log::info('Order marked as payment failed', ['order_id' => $order->id]);
        }
    }
}
