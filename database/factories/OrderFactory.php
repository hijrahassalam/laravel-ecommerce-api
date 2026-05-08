<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'session_id' => $this->faker->uuid(),
            'status' => Order::STATUS_PENDING,
            'total_amount' => $this->faker->randomFloat(2, 20, 500),
            'currency' => 'usd',
            'items_count' => $this->faker->numberBetween(1, 5),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }
}
