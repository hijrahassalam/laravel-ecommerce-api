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
            'stripe_payment_intent_id' => 'pi_' . $this->faker->uuid(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_REFUNDED,
            'paid_at' => now(),
            'stripe_payment_intent_id' => 'pi_' . $this->faker->uuid(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_FAILED,
        ]);
    }
}
