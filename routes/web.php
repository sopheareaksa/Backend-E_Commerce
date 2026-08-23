<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'Zodiac Store API is running successfully!',
        'endpoints' => [
            'products' => '/api/products',
            'featured' => '/api/products/featured',
            'categories' => '/api/products/category/{slug}',
        ],
        'timestamp' => now()->toIso8601String()
    ]);
});
