<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_products(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_list_all_products_including_inactive(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/products?active_only=false');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_search_products_by_name(): void
    {
        Product::factory()->create(['name' => 'Classic T-Shirt']);
        Product::factory()->create(['name' => 'Slim Fit Jeans']);
        Product::factory()->create(['name' => 'Running Shoes']);

        $response = $this->getJson('/api/products?search=shirt');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Classic T-Shirt');
    }

    public function test_can_show_single_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 49.99,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Product')
            ->assertJsonPath('data.price', '49.99');
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/products/9999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Product not found');
    }

    public function test_can_create_product(): void
    {
        $payload = [
            'name' => 'New Product',
            'description' => 'A brand new product',
            'price' => 99.99,
            'stock' => 50,
            'image_url' => 'https://example.com/image.jpg',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Product')
            ->assertJsonPath('data.price', '99.99')
            ->assertJsonPath('message', 'Product created');

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'price' => 99.99,
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock']);
    }

    public function test_create_validates_price_is_numeric(): void
    {
        $payload = [
            'name' => 'Test',
            'price' => 'not-a-number',
            'stock' => 10,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('message', 'Product updated');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_returns_404_for_nonexistent_product(): void
    {
        $response = $this->putJson('/api/products/9999', ['name' => 'Test']);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Product not found');
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product deleted');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_delete_returns_404_for_nonexistent_product(): void
    {
        $response = $this->deleteJson('/api/products/9999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Product not found');
    }
}
