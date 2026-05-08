<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_empty_cart(): void
    {
        Cart::create(['session_id' => 'test-session-empty']);

        $response = $this->getJson('/api/cart', [
            'X-Session-ID' => 'test-session-empty',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.item_count', 0);
        $this->assertEquals(0.0, $response->json('data.subtotal'));
    }

    public function test_can_add_item_to_cart(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
            'stock' => 100,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], ['X-Session-ID' => 'test-session']);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('cart.item_count', 2);
        $this->assertEquals(59.98, $response->json('cart.subtotal'));
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
            'stock' => 100,
        ]);

        $cart = Cart::create(['session_id' => 'test-session-inc']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 29.99,
            'subtotal' => 59.98,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ], ['X-Session-ID' => 'test-session-inc']);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity', 5)
            ->assertJsonPath('cart.item_count', 5);
        $this->assertEquals(149.95, $response->json('cart.subtotal'));
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $product = Product::factory()->create([
            'stock' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], ['X-Session-ID' => 'test-session-oos']);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Product is out of stock');
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 10,
        ], ['X-Session-ID' => 'test-session-over']);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Only 5 units available');
    }

    public function test_cannot_add_nonexistent_product(): void
    {
        $response = $this->postJson('/api/cart/items', [
            'product_id' => 9999,
            'quantity' => 1,
        ], ['X-Session-ID' => 'test-session-bad']);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Product not found');
    }

    public function test_can_update_cart_item_quantity(): void
    {
        $product = Product::factory()->create(['stock' => 100]);
        $cart = Cart::create(['session_id' => 'test-session-upd']);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $response = $this->putJson("/api/cart/items/{$item->id}", [
            'quantity' => 5,
        ], ['X-Session-ID' => 'test-session-upd']);

        $response->assertStatus(200)
            ->assertJsonPath('data.quantity', 5);
    }

    public function test_cannot_update_cart_item_beyond_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $cart = Cart::create(['session_id' => 'test-session-upd2']);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $response = $this->putJson("/api/cart/items/{$item->id}", [
            'quantity' => 20,
        ], ['X-Session-ID' => 'test-session-upd2']);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Only 10 units available');
    }

    public function test_can_remove_cart_item(): void
    {
        $product = Product::factory()->create();
        $cart = Cart::create(['session_id' => 'test-session-rem']);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => $product->price,
        ]);

        $response = $this->deleteJson("/api/cart/items/{$item->id}", [], [
            'X-Session-ID' => 'test-session-rem',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Item removed from cart');
    }

    public function test_can_clear_cart(): void
    {
        $product = Product::factory()->create();
        $cart = Cart::create(['session_id' => 'test-session-clr']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => $product->price,
        ]);

        $response = $this->deleteJson('/api/cart', [], [
            'X-Session-ID' => 'test-session-clr',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Cart cleared');
    }
}
