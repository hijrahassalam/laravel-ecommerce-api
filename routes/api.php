<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

// Products
Route::apiResource('products', ProductController::class)->only(['index', 'show']);

// Cart
Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/items', [CartController::class, 'addItem']);
Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
Route::delete('/cart', [CartController::class, 'clear']);
