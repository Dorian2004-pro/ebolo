<?php

use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
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
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

    Route::middleware('artisan')->group(function () {
        Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
        Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
        Route::post('/bookings/{id}/release', [BookingController::class, 'releaseEscrow']);
    });

    Route::middleware('artisan')->prefix('artisan')->group(function () {
        Route::get('/profile', [ArtisanController::class, 'profile']);
        Route::put('/profile', [ArtisanController::class, 'updateProfile']);
        Route::get('/availability', [ArtisanController::class, 'availability']);
        Route::put('/availability', [ArtisanController::class, 'updateAvailability']);
        Route::get('/identity-verification', [ArtisanController::class, 'identityVerification']);
        Route::post('/identity-verification', [ArtisanController::class, 'submitIdentityVerification']);
        Route::get('/services', [ServiceController::class, 'artisanIndex']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    });

    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirm']);

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::get('/chat/{userId}/messages', [ChatController::class, 'index']);
    Route::post('/chat/send', [ChatController::class, 'send']);
});
