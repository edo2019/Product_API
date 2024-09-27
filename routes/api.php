<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//List of user Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


// Group routes with throttle middleware to limit requests
Route::middleware(['auth:sanctum','throttle:50,60'])->group(function () {

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
    
    //bulk operations 
    Route::put('/products/bulk-update', [ProductController::class, 'bulkUpdate']);

});