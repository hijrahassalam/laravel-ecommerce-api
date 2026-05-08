<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_orders_as_admin(): void
    {
        Order::factory()->count(5)->create();
        Order::factory()->count(3)->paid()->create();

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(8, 'data');
    }

    public function test_can_filter_orders_by_status(): void
    {
        Order::factory()->count(3)->create(['status' => Order::STATUS_PAID]);
        Order::factory()->count(2)->create(['status' => Order::STATUS_PENDING]);

        $response = $this->getJson('/api/admin/orders?status=paid');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_order_stats(): void
    {
        Order::factory()->count(5)->paid()->create(['total_amount' => 100]);
        Order::factory()->count(2)->pending()->create();
        Order::factory()->refunded()->create(['total_amount' => 50]);

        $response = $this->getJson('/api/admin/orders/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_orders', 8)
            ->assertJsonPath('data.paid_orders', 5)
            ->assertJsonPath('data.pending_orders', 2)
            ->assertJsonPath('data.total_revenue', 500.0)
            ->assertJsonPath('data.average_order_value', 100.0);
    }

    public function test_can_show_single_order_with_items(): void
    {
        $order = Order::factory()->paid()->create();
        OrderItem::factory()->count(3)->forOrder($order)->create();

        $response = $this->getJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_can_update_order_status(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $response = $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'paid',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_invalid_status_rejected(): void
    {
        $order = Order::factory()->create();

        $response = $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_cannot_refund_unpaid_order(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $response = $this->postJson("/api/orders/{$order->id}/refund");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Only paid orders can be refunded');
    }

    public function test_cannot_refund_already_refunded_order(): void
    {
        $order = Order::factory()->refunded()->create();

        $response = $this->postJson("/api/orders/{$order->id}/refund");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Order already refunded');
    }

    public function test_can_get_order_by_session(): void
    {
        $order = Order::factory()->paid()->create(['session_id' => 'my-session']);

        $response = $this->getJson("/api/orders/{$order->id}", [
            'X-Session-ID' => 'my-session',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_order_404_for_wrong_session(): void
    {
        $order = Order::factory()->paid()->create(['session_id' => 'other-session']);

        $response = $this->getJson("/api/orders/{$order->id}", [
            'X-Session-ID' => 'my-session',
        ]);

        $response->assertStatus(404);
    }
}
