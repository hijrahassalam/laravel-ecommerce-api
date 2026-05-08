<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with('items');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $orders = $query->paginate(20);

        return response()->json($orders);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with(['items.product', 'user'])->find($id);

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

        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,paid,failed,refunded',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'data' => $order->fresh(),
            'message' => 'Order status updated',
        ]);
    }

    public function stats(): JsonResponse
    {
        $totalOrders = Order::count();
        $paidOrders = Order::where('status', Order::STATUS_PAID)->count();
        $pendingOrders = Order::where('status', Order::STATUS_PENDING)->count();
        $refundedOrders = Order::where('status', Order::STATUS_REFUNDED)->count();

        $totalRevenue = Order::where('status', Order::STATUS_PAID)
            ->sum('total_amount');

        $avgOrderValue = Order::where('status', Order::STATUS_PAID)
            ->avg('total_amount') ?? 0;

        return response()->json([
            'data' => [
                'total_orders' => $totalOrders,
                'paid_orders' => $paidOrders,
                'pending_orders' => $pendingOrders,
                'refunded_orders' => $refundedOrders,
                'total_revenue' => round($totalRevenue, 2),
                'average_order_value' => round($avgOrderValue, 2),
            ],
        ]);
    }
}
