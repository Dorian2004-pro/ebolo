<?php

use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\ClientDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => view('client', [
    'screen' => $request->session()->has('ebolo_token') ? 'catalog' : 'welcome',
    'user' => $request->session()->get('ebolo_user'),
]))->name('home');

Route::get('/inscription', fn () => view('client', ['screen' => 'register', 'user' => null]))->name('register');
Route::get('/connexion', fn () => view('client', ['screen' => 'login', 'user' => null]))->name('login');

Route::post('/client-api/inscription', [ClientAuthController::class, 'register'])->middleware('throttle:5,1')->name('client.auth.register');
Route::post('/client-api/connexion', [ClientAuthController::class, 'login'])->middleware('throttle:6,1')->name('client.auth.login');
Route::post('/client-api/deconnexion', [ClientAuthController::class, 'logout'])->name('client.auth.logout');

Route::prefix('/client-api')->name('client.data.')->group(function (): void {
    Route::get('/services', [ClientDataController::class, 'services'])->name('services');
    Route::get('/rendez-vous', [ClientDataController::class, 'bookings'])->name('bookings');
    Route::post('/rendez-vous', [ClientDataController::class, 'createBooking'])->name('bookings.create');
});

Route::get('/mes-rendez-vous', fn (Request $request) => $request->session()->has('ebolo_token')
    ? view('client', ['screen' => 'bookings', 'user' => $request->session()->get('ebolo_user')])
    : redirect()->route('login'))->name('bookings');
