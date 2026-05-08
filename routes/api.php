<?php

use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

// Products (public read-only)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Cart
Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/items', [CartController::class, 'addItem']);
Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
Route::delete('/cart', [CartController::class, 'clear']);

// Checkout
Route::post('/checkout', [OrderController::class, 'getCheckoutSession']);

// Orders (customer)
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);

// Stripe Webhook (raw body, no CSRF)
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['Illuminate\Foundation\Http\Middleware\VerifyCsrfToken']);

// Admin (basic — no auth middleware for demo purposes)
Route::prefix('admin')->group(function () {
    Route::get('/orders', [OrderAdminController::class, 'index']);
    Route::get('/orders/stats', [OrderAdminController::class, 'stats']);
    Route::get('/orders/{id}', [OrderAdminController::class, 'show']);
    Route::patch('/orders/{id}/status', [OrderAdminController::class, 'updateStatus']);
});

// Refund
Route::post('/orders/{id}/refund', [RefundController::class, 'create']);
