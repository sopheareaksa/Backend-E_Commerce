<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\User;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'Zodiac Store API is running successfully!',
        'endpoints' => [
            'setup_database' => '/setup-db',
            'products' => '/api/products',
            'featured' => '/api/products/featured',
            'categories' => '/api/products/category/{slug}',
        ],
        'timestamp' => now()->toIso8601String()
    ]);
});

// Setup & Seed Database via Web URL (Works without Render Shell)
Route::get('/setup-db', function () {
    try {
        Artisan::call('migrate', [
            '--force' => true,
            '--seed' => true,
        ]);
        $output = Artisan::output();

        return response()->json([
            'status' => 'success',
            'message' => 'Database migrated and seeded successfully!',
            'details' => $output,
            'product_count' => Product::count(),
            'user_count' => User::count(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'hint' => 'Check your DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in Render Environment variables.',
        ], 500);
    }
});
