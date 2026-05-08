<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('items');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($orders);
    }

    public function stats(): JsonResponse
    {
        $totalOrders = Order::count();
        $paidOrders = Order::where('status', Order::STATUS_PAID)->count();
        $pendingOrders = Order::where('status', Order::STATUS_PENDING)->count();
        $totalRevenue = Order::where('status', Order::STATUS_PAID)->sum('total_amount');
        $avgOrderValue = $paidOrders > 0 ? $totalRevenue / $paidOrders : 0;

        return response()->json([
            'data' => [
                'total_orders' => $totalOrders,
                'paid_orders' => $paidOrders,
                'pending_orders' => $pendingOrders,
                'total_revenue' => round($totalRevenue, 2),
                'average_order_value' => round($avgOrderValue, 2),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json(['data' => $order]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $request->validate([
            'status' => 'required|string|in:pending,paid,failed,refunded',
        ]);

        $order->update(['status' => $request->input('status')]);

        return response()->json(['data' => $order->fresh()]);
    }
}
