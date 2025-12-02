<?php

use App\Http\Controllers\Api\AdminPostController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminPostsController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicPostController;
use App\Http\Controllers\Api\PublicPostsController;
use App\Http\Controllers\Api\SellerPostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes

Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // User CRUD routes for admin
    Route::prefix('admin')->group(function () {
        Route::apiResource('/users', UserController::class);
    });

    // Seller Posts Routes
    Route::prefix('seller')->middleware('role:seller')->group(function () {
        Route::apiResource('posts', SellerPostController::class);

        //Payment
        Route::post('/payments/create/{post}', [PaymentController::class, 'create'])->name('payments.create');
    });

    // Admin Posts Routes
    Route::prefix('admin/posts')->middleware('role:admin')->group(function () {
        Route::patch('/{post}/status', [AdminPostController::class, 'updateStatus']);
        Route::get('/{post}', [AdminPostController::class, 'show']);
        Route::get('/', [AdminPostController::class, 'index']);
        // Route::patch('/{id}/approve', [AdminPostsController::class, 'approve']);
        // Route::patch('/{id}/reject', [AdminPostsController::class, 'reject']);
        // Route::delete('/{id}', [AdminPostsController::class, 'deletePost']);
        // Route::patch('/{id}/status', [AdminPostsController::class, 'updateStatus']);
        // Route::get('/', [AdminPostsController::class, 'getAllPosts']);
        // Route::get('/{id}', [AdminPostsController::class, 'getPostById']);
    });
});

// Vnpay callback route
Route::get('/payments/vnpay-return', [PaymentController::class, 'vnpayReturn'])->name('payments.vnpayReturn');

// Public posts routes - accessible without auth
Route::prefix('posts')->group(function () {
    // Route::get('/search', [PublicPostsController::class, 'search']);
    // Route::get('/featured', [PublicPostsController::class, 'getFeatured']);
    // Route::get('/brands', [PublicPostsController::class, 'getBrands']);
    // Route::get('/models', [PublicPostsController::class, 'getModels']);
    // Route::get('/seller/{sellerId}', [PublicPostsController::class, 'getBySeller']);
    Route::get('/', [PublicPostController::class, 'index']);
    Route::get('/{post}', [PublicPostController::class, 'show']);
});
