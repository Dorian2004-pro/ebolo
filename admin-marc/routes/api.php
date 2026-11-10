<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| API d'administration EBOLO — préfixe /api/admin
| Authentification : jeton Bearer Sanctum (en-tête « Authorization: Bearer … »).
*/
Route::prefix('admin')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:admin-login');

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
        Route::post('users/{user}/suspend', [UserController::class, 'suspend']);
        Route::post('users/{user}/activate', [UserController::class, 'activate']);

        Route::get('providers', [ProviderController::class, 'index']);
        Route::post('providers/{provider}/approve', [ProviderController::class, 'approve']);
        Route::post('providers/{provider}/reject', [ProviderController::class, 'reject']);

        Route::get('services', [ServiceController::class, 'index']);
        Route::patch('services/{service}', [ServiceController::class, 'update']);

        Route::get('bookings', [BookingController::class, 'index']);
        Route::get('payments', [TransactionController::class, 'index']);

        Route::get('disputes', [DisputeController::class, 'index']);
        Route::patch('disputes/{dispute}', [DisputeController::class, 'update']);
    });
});
