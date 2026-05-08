<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

// Products
Route::apiResource('products', ProductController::class);
