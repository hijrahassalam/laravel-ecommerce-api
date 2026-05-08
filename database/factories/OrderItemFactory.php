<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 9.99, 199.99);
        $quantity = $this->faker->numberBetween(1, 3);

        return [
            'product_id' => null,
            'product_name' => $this->faker->words(2, true),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $price * $quantity,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }
}
