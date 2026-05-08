<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset Stripe mock if any
    }

    public function test_can_get_checkout_session_with_items_in_cart(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Create cart via session
        $cart = Cart::create(['session_id' => 'test-session']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'checkout_url',
                    'session_id',
                    'order_id',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'session_id' => 'test-session',
            'status' => 'pending',
        ]);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        Cart::create(['session_id' => 'test-session']);

        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'test-session',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Cart is empty');
    }

    public function test_checkout_fails_with_empty_session(): void
    {
        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'nonexistent-session',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Cart is empty');
    }

    public function test_order_created_before_checkout(): void
    {
        $product = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 199.99,
            'stock' => 5,
            'is_active' => true,
        ]);

        $cart = Cart::create(['session_id' => 'test-session-2']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => $product->price,
        ]);

        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'test-session-2',
        ]);

        $response->assertStatus(200);

        // Order should be created in pending state before payment
        $this->assertDatabaseHas('orders', [
            'session_id' => 'test-session-2',
            'status' => 'pending',
            'total_amount' => $product->price,
        ]);
    }

    public function test_checkout_creates_order_items(): void
    {
        $product1 = Product::factory()->create(['price' => 10.00, 'is_active' => true]);
        $product2 = Product::factory()->create(['price' => 25.00, 'is_active' => true]);

        $cart = Cart::create(['session_id' => 'test-session-3']);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product1->id, 'quantity' => 2, 'price' => 10.00, 'subtotal' => 20.00]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product2->id, 'quantity' => 1, 'price' => 25.00, 'subtotal' => 25.00]);

        $response = $this->postJson('/api/checkout', [], [
            'X-Session-ID' => 'test-session-3',
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $orderId = $data['order_id'];

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);
    }
}
