<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cannot_checkout_with_empty_cart(): void
    {
        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Cart is empty');
    }

    public function test_checkout_validates_stripe_not_configured(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $cart = Cart::create(['session_id' => 'test-session']);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'Stripe is not configured');
    }

    public function test_order_created_before_checkout(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
            'stock' => 10,
            'is_active' => true,
        ]);
        $cart = Cart::create(['session_id' => 'test-session']);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 29.99,
        ]);

        // Mock Stripe
        $stripeMock = Mockery::mock('overload:Stripe\StripeClient');
        $stripeMock->checkout = Mockery::mock();
        $stripeMock->checkout->sessions = Mockery::mock();
        $stripeMock->checkout->sessions->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'id' => 'cs_test_mock',
                'url' => 'https://checkout.stripe.com/test',
                'payment_intent' => 'pi_mock',
            ]);

        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'session_id' => 'test-session',
            'stripe_session_id' => 'cs_test_mock',
            'status' => Order::STATUS_PENDING,
        ]);
    }

    public function test_can_list_orders(): void
    {
        $order = Order::factory()->create(['session_id' => 'test-session']);

        $response = $this->getJson('/api/orders', [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_single_order(): void
    {
        $order = Order::factory()->create([
            'session_id' => 'test-session',
            'total_amount' => 99.99,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}", [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_amount', '99.99');
    }

    public function test_order_404_for_other_session(): void
    {
        $order = Order::factory()->create(['session_id' => 'other-session']);

        $response = $this->getJson("/api/orders/{$order->id}", [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Order not found');
    }
}
