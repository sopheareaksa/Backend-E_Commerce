<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BakongPaymentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPasswordWithOtp']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/category/{slug}', [ProductController::class, 'byCategory']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Bakong KHQR endpoints (compatible with ASP.NET PaymentController routes & direct API calls)
Route::post('/bakong/generate-khqr', [BakongPaymentController::class, 'generateKhqr']);
Route::post('/bakong/check-payment', [BakongPaymentController::class, 'checkPayment']);
Route::post('/bakong/simulate-payment', [BakongPaymentController::class, 'simulatePayment']);
Route::post('/bakong/verify-khqr', [BakongPaymentController::class, 'verifyKhqr']);
Route::post('/Payment/generate-khqr', [BakongPaymentController::class, 'generateKhqr']);
Route::post('/Payment/check-payment', [BakongPaymentController::class, 'checkPayment']);
Route::post('/Payment/simulate-payment', [BakongPaymentController::class, 'simulatePayment']);
Route::post('/Payment/verify-khqr', [BakongPaymentController::class, 'verifyKhqr']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::post('/payments/aba/create', [PaymentController::class, 'createAbaCheckout']);
    Route::get('/payments/aba/status/{order}', [PaymentController::class, 'paymentStatus']);
    Route::post('/payments/aba/simulate/{order}', [PaymentController::class, 'simulatePayment']);

    Route::post('/payments/bakong/generate-khqr', [BakongPaymentController::class, 'generateKhqr']);
    Route::post('/payments/bakong/check-payment', [BakongPaymentController::class, 'checkPayment']);
    Route::post('/payments/bakong/verify-khqr', [BakongPaymentController::class, 'verifyKhqr']);

    Route::post('/contact', [ContactController::class, 'store']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::put('/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
    });
});

// ABA PayWay calls this URL directly; it must not require user authentication.
Route::post('/payments/aba/callback', [PaymentController::class, 'abaCallback']);
