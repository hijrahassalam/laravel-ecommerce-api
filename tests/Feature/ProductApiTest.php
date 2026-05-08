<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'stock', 'description', 'image_url', 'is_active', 'created_at'],
                ],
            ]);
    }

    public function test_can_search_products(): void
    {
        Product::factory()->create(['name' => 'Laptop']);
        Product::factory()->create(['name' => 'Mouse']);
        Product::factory()->create(['name' => 'Keyboard']);

        $response = $this->getJson('/api/products?search=key');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Keyboard');
    }

    public function test_can_show_single_product(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Test Product');
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/products/9999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Product not found');
    }

    public function test_inactive_products_hidden_by_default(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_inactive_products_shown_when_requested(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/products?active_only=false');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}
