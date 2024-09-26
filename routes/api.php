<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Group routes with throttle middleware to limit requests
Route::middleware('throttle:50,60')->group(function () {

    // List all products (Limited to 50 requests per hour)
    Route::get('/products', [ProductController::class, 'list']);

    // Search products by keyword in the product title (case-insensitive)
    Route::get('/products/search/{keyword}', [ProductController::class, 'search']);

    // Filter products by category and price range using query parameters
    Route::get('/products/filter', [ProductController::class, 'filter']);

    // Sort products by a given field (e.g., price or title) using query parameters
    Route::get('/products/sort', [ProductController::class, 'sort']);

    // Get product details by ID
    Route::get('/products/{id}', [ProductController::class, 'show']);

    // Update product price by ID
    Route::put('/products/{id}/price', [ProductController::class, 'updatePrice']);
    
    // Additional complex queries or bulk operations can be added as needed
});