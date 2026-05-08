<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Classic T-Shirt',
                'description' => 'Premium cotton t-shirt. Comfortable for everyday wear.',
                'price' => 29.99,
                'stock' => 100,
                'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500',
                'is_active' => true,
            ],
            [
                'name' => 'Slim Fit Jeans',
                'description' => 'Modern slim fit denim jeans. Perfect for casual and semi-formal occasions.',
                'price' => 79.99,
                'stock' => 50,
                'image_url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500',
                'is_active' => true,
            ],
            [
                'name' => 'Running Sneakers',
                'description' => 'Lightweight running shoes with cushioned sole for maximum comfort.',
                'price' => 119.99,
                'stock' => 30,
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500',
                'is_active' => true,
            ],
            [
                'name' => 'Leather Belt',
                'description' => 'Genuine leather belt with brushed metal buckle.',
                'price' => 45.00,
                'stock' => 80,
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500',
                'is_active' => true,
            ],
            [
                'name' => 'Baseball Cap',
                'description' => 'Adjustable cotton twill cap. One size fits all.',
                'price' => 24.99,
                'stock' => 0, // Out of stock
                'image_url' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=500',
                'is_active' => true,
            ],
            [
                'name' => 'Wool Sweater',
                'description' => 'Soft merino wool sweater. Ideal for layering.',
                'price' => 89.99,
                'stock' => 25,
                'image_url' => 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=500',
                'is_active' => true,
            ],
            [
                'name' => 'Canvas Backpack',
                'description' => 'Durable canvas backpack with laptop compartment.',
                'price' => 59.99,
                'stock' => 40,
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500',
                'is_active' => false, // Inactive product
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
