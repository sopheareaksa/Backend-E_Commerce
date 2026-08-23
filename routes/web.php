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
            'clean_duplicates' => '/clean-duplicates',
            'products' => '/api/products',
            'featured' => '/api/products/featured',
            'categories' => '/api/products/category/{slug}',
        ],
        'timestamp' => now()->toIso8601String()
    ]);
});

// Remove duplicate products keeping the newest (edited) ones
Route::get('/clean-duplicates', function () {
    $products = Product::orderBy('product_id', 'desc')->get();
    $seen = [];
    $deleted = 0;

    foreach ($products as $p) {
        $key = strtolower(trim($p->product_name));
        if (isset($seen[$key])) {
            $p->delete();
            $deleted++;
        } else {
            $seen[$key] = true;
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => "Cleaned {$deleted} duplicate products. Remaining unique products: " . Product::count(),
        'products_in_store' => Product::count(),
    ]);
});

// Setup & Seed Database via Web URL
Route::get('/setup-db', function () {
    try {
        Artisan::call('migrate', [
            '--force' => true,
        ]);

        try {
            Artisan::call('db:seed', ['--force' => true]);
        } catch (\Throwable $e) {
            // Already seeded
        }

        // Auto-clean duplicates on setup-db keeping the newest
        $products = Product::orderBy('product_id', 'desc')->get();
        $seen = [];
        foreach ($products as $p) {
            $key = strtolower(trim($p->product_name));
            if (isset($seen[$key])) {
                $p->delete();
            } else {
                $seen[$key] = true;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Database is 100% ready, migrated, and deduplicated!',
            'products_in_store' => Product::count(),
            'users_registered' => User::count(),
            'store_url' => 'https://frontend-e-commerce-rose-phi.vercel.app'
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'hint' => 'Check your DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in Render Environment variables.',
        ], 500);
    }
});
