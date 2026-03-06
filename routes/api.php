<?php

use App\Http\Controllers\Api\AdminPostController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminPostsController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicPostController;
use App\Http\Controllers\Api\PublicPostsController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
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
        Route::get('/profile', [AuthController::class, 'profile']);
    });

    // User CRUD routes for admin
    Route::prefix('admin')->group(function () {
        Route::apiResource('/users', UserController::class);
    });

     // User routes
    Route::prefix('user')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    Route::middleware('role:buyer')->group(function () {
        Route::post('sellers/{sellerId}/reviews', [ReviewController::class, 'store']);
    });

    // Seller Posts Routes
    Route::prefix('seller')->middleware('role:seller')->group(function () {
        Route::apiResource('posts', SellerPostController::class);
        //Payment
        Route::post('/payments/create/{post}', [PaymentController::class, 'create'])->name('payments.create');
    });

    Route::prefix('reports')->group(function () {
        Route::post('/report-buyer', [ReportController::class, 'reportBuyer']);
        Route::get('/my-reports', [ReportController::class, 'myReports']);
        Route::delete('/{reportId}', [ReportController::class, 'destroy'])->middleware('role:admin,seller');
    });

    Route::post('/posts/{postId}/report', [ReportController::class, 'reportPost'])->middleware('role:buyer');

    // Admin Posts Routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::prefix('posts')->group(function () {
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

        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportController::class, 'index']);
            Route::get('/{reportId}', [ReportController::class, 'show']);
            Route::patch('/{reportId}/status', [ReportController::class, 'updateStatus']);
        });


    });

    // Favorites Routes
    Route::apiResource('favorites', FavoriteController::class)
        ->parameters(['favorites' => 'postId']);

});

// Vnpay callback route
Route::get('/payments/vnpay-return', [PaymentController::class, 'vnpayReturn'])->name('payments.vnpayReturn');

// Public posts routes - accessible without auth
Route::prefix('posts')->group(function () {
    Route::get('/search', [PublicPostController::class, 'search']);
    Route::get('/', [PublicPostController::class, 'index']);
    Route::get('/{post}', [PublicPostController::class, 'show']);
});

Route::get('sellers/{sellerId}/reviews', [ReviewController::class, 'index']);
