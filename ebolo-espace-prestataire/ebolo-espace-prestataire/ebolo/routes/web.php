<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Vue
|--------------------------------------------------------------------------
| Toutes les URLs « humaines » renvoient la coquille Blade qui monte l'app Vue.
| La navigation est ensuite gérée côté client par Vue Router (/pro, /pro/verification…).
*/
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api|up|storage|build).*$')
    ->name('spa');
