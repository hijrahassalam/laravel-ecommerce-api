<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);
        $cart->load('items.product');

        return response()->json([
            'data' => [
                'id' => $cart->id,
                'items' => $cart->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_image' => $item->product->image_url,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ]),
                'item_count' => $cart->item_count,
                'subtotal' => $cart->subtotal,
            ],
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'quantity' => 'sometimes|integer|min:1|max:99',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        $product = Product::find($validated['product_id']);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if (!$product->isInStock()) {
            return response()->json(['message' => 'Product is out of stock'], 400);
        }

        $cart = $this->getCart($request);
        $quantity = $validated['quantity'] ?? 1;

        $existingItem = $cart->items()->where('product_id', $product->id)->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;

            if ($newQuantity > $product->stock) {
                return response()->json([
                    'message' => "Only {$product->stock} units available",
                ], 400);
            }

            $existingItem->update(['quantity' => $newQuantity]);
            $item = $existingItem;
        } else {
            $item = $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
        }

        $cart->load('items.product');

        return response()->json([
            'data' => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ],
            'cart' => [
                'item_count' => $cart->item_count,
                'subtotal' => $cart->subtotal,
            ],
            'message' => 'Item added to cart',
        ], 201);
    }

    public function updateItem(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1|max:99',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        $cart = $this->getCart($request);
        $item = $cart->items()->find($id);

        if (!$item) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $product = $item->product;

        if ($validated['quantity'] > $product->stock) {
            return response()->json([
                'message' => "Only {$product->stock} units available",
            ], 400);
        }

        $item->update(['quantity' => $validated['quantity']]);
        $cart->load('items.product');

        return response()->json([
            'data' => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
            ],
            'cart' => [
                'item_count' => $cart->item_count,
                'subtotal' => $cart->subtotal,
            ],
            'message' => 'Cart item updated',
        ]);
    }

    public function removeItem(Request $request, int $id): JsonResponse
    {
        $cart = $this->getCart($request);
        $item = $cart->items()->find($id);

        if (!$item) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $item->delete();
        $cart->load('items.product');

        return response()->json([
            'cart' => [
                'item_count' => $cart->item_count,
                'subtotal' => $cart->subtotal,
            ],
            'message' => 'Item removed from cart',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);
        $cart->items()->delete();
        $cart->load('items.product');

        return response()->json([
            'cart' => [
                'item_count' => 0,
                'subtotal' => 0.0,
            ],
            'message' => 'Cart cleared',
        ]);
    }

    private function getCart(Request $request): Cart
    {
        if ($request->user()) {
            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        } else {
            $sessionId = $request->header('X-Session-ID') ?? $request->session()->get('_token');
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        }

        return $cart;
    }
}
