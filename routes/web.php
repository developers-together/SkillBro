<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');

Route::view('/auth/reset-password/{token}', 'app')->name('password.reset');

Route::view('/{any?}', 'app')
    ->where('any', '^(?!api|storage).*$')
    ->name('spa.fallback');
