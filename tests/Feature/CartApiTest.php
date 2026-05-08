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
        $response = $this->getJson('/api/cart', [
            'X-Session-ID' => 'test-session-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.item_count', 0)
            ->assertJsonPath('data.subtotal', 0.0);
    }

    public function test_can_add_item_to_cart(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], ['X-Session-ID' => 'test-session-123']);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Item added to cart')
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.price', '29.99')
            ->assertJsonPath('cart.item_count', 2)
            ->assertJsonPath('cart.subtotal', '59.98');
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], ['X-Session-ID' => 'test-session-123']);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ], ['X-Session-ID' => 'test-session-123']);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity', 5)
            ->assertJsonPath('cart.item_count', 5)
            ->assertJsonPath('cart.subtotal', '149.95');
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $product = Product::factory()->create([
            'stock' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
        ], ['X-Session-ID' => 'test-session-123']);

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
        ], ['X-Session-ID' => 'test-session-123']);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Only 5 units available');
    }

    public function test_cannot_add_nonexistent_product(): void
    {
        $response = $this->postJson('/api/cart/items', [
            'product_id' => 9999,
        ], ['X-Session-ID' => 'test-session-123']);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Product not found');
    }

    public function test_can_update_cart_item_quantity(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'is_active' => true]);
        $cart = Cart::create(['session_id' => 'test-session-123']);
        $item = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $response = $this->putJson("/api/cart/items/{$item->id}", [
            'quantity' => 5,
        ], ['X-Session-ID' => 'test-session-123']);

        $response->assertStatus(200)
            ->assertJsonPath('data.quantity', 5)
            ->assertJsonPath('message', 'Cart item updated');
    }

    public function test_cannot_update_cart_item_beyond_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true]);
        $cart = Cart::create(['session_id' => 'test-session-123']);
        $item = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $response = $this->putJson("/api/cart/items/{$item->id}", [
            'quantity' => 10,
        ], ['X-Session-ID' => 'test-session-123']);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Only 5 units available');
    }

    public function test_can_remove_cart_item(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $cart = Cart::create(['session_id' => 'test-session-123']);
        $item = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $response = $this->deleteJson("/api/cart/items/{$item->id}", [], [
            'X-Session-ID' => 'test-session-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Item removed from cart');

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_can_clear_cart(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $cart = Cart::create(['session_id' => 'test-session-123']);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $response = $this->deleteJson('/api/cart', [], [
            'X-Session-ID' => 'test-session-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Cart cleared')
            ->assertJsonPath('cart.item_count', 0);

        $this->assertEquals(0, CartItem::count());
    }
}
