<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Laravel\Socialite\Facades\Socialite;

Route::get("/401", [AuthController::class, 'unauthorized'])->name('login');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post("validate", [AuthController::class, "validateToken"]);
    Route::post("logout", [AuthController::class, "logout"]);

    Route::get('google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('me', [ProfileController::class, 'me']);
    });
});