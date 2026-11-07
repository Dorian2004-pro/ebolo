<?php

use Illuminate\Support\Facades\Route;

// Racine -> panneau d'administration
Route::redirect('/', '/admin');

// Toute URL /admin/* est servie par l'application Vue (vue-router en mode « history »).
Route::view('/admin/{any?}', 'app')->where('any', '.*')->name('admin.spa');
