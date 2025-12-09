<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Auth routes will be added here
});

Route::middleware('auth')->group(function () {
    // Authenticated user routes
});
