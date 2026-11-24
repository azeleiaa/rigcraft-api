<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuilderController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // -------------------------------------------------------------
    // Public Routes
    // -------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    });

    // Products
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);

    // PC Builder Rule-Based Compatibility Validation (FR-004)
    Route::post('builder/validate', [BuilderController::class, 'validateBuild']);

    // Voucher validation
    Route::post('vouchers/validate', [OrderController::class, 'validateVoucher']);

    // Public reviews (FR-007)
    Route::get('reviews', [ReviewController::class, 'index']);

    // -------------------------------------------------------------
    // Authenticated User Routes (Customer / Admin)
    // -------------------------------------------------------------
    Route::middleware('auth:api')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Cart
        Route::get('cart', [CartController::class, 'getCart']);
        Route::post('cart', [CartController::class, 'addToCart']);
        Route::put('cart/{productId}', [CartController::class, 'updateCartItem']);
        Route::delete('cart/{productId}', [CartController::class, 'removeFromCart']);
        Route::delete('cart', [CartController::class, 'clearCart']);

        // Orders
        Route::post('orders', [OrderController::class, 'checkout']);
        Route::get('orders', [OrderController::class, 'userOrders']);
        Route::put('orders/{id}/cancel', [OrderController::class, 'cancelOrder']);

        // Reviews (submit as logged-in user)
        Route::post('reviews', [ReviewController::class, 'store']);
    });

    // -------------------------------------------------------------
    // Admin Protected Routes (FR-002, FR-007)
    // -------------------------------------------------------------
    Route::middleware(['auth:api', EnsureAdmin::class])->prefix('admin')->group(function () {
        Route::get('stats', [AdminController::class, 'dashboardStats']);
        Route::get('products', [AdminController::class, 'products']);
        Route::post('products', [AdminController::class, 'storeProduct']);
        Route::put('products/{id}', [AdminController::class, 'updateProduct']);
        Route::delete('products/{id}', [AdminController::class, 'deleteProduct']);
        Route::get('orders', [AdminController::class, 'orders']);
        Route::put('orders/{id}', [AdminController::class, 'updateOrderStatus']);
        Route::get('reviews', [AdminController::class, 'reviews']);
        Route::get('vouchers', [AdminController::class, 'vouchers']);
        Route::post('vouchers', [AdminController::class, 'storeVoucher']);
        Route::put('vouchers/{id}', [AdminController::class, 'updateVoucher']);
        Route::delete('vouchers/{id}', [AdminController::class, 'deleteVoucher']);
    });
});

