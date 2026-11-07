<?php

use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DisputeController as AdminDisputeController;
use App\Http\Controllers\Admin\ProviderController as AdminProviderController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    Route::get('/artisans', [ArtisanController::class, 'index']);
    Route::get('/artisans/{id}', [ArtisanController::class, 'show']);
    Route::post('/artisans', [ArtisanController::class, 'store']);
    Route::put('/artisans/{id}', [ArtisanController::class, 'update']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirm']);

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::get('/chat/{userId}/messages', [ChatController::class, 'index']);
    Route::post('/chat/send', [ChatController::class, 'send']);
});

Route::prefix('admin')->group(function () {
    Route::post('/auth/login', [AdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/auth/me', [AdminAuthController::class, 'me']);
        Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::apiResource('users', AdminUserController::class)->except(['create', 'edit']);
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
        Route::post('/users/{user}/activate', [AdminUserController::class, 'activate']);

        Route::get('/providers', [AdminProviderController::class, 'index']);
        Route::post('/providers/{provider}/approve', [AdminProviderController::class, 'approve']);
        Route::post('/providers/{provider}/reject', [AdminProviderController::class, 'reject']);
        Route::get('/services', [AdminServiceController::class, 'index']);
        Route::patch('/services/{service}', [AdminServiceController::class, 'update']);
        Route::get('/bookings', [AdminBookingController::class, 'index']);
        Route::get('/payments', [AdminTransactionController::class, 'index']);
        Route::get('/disputes', [AdminDisputeController::class, 'index']);
        Route::patch('/disputes/{dispute}', [AdminDisputeController::class, 'update']);
    });
});
