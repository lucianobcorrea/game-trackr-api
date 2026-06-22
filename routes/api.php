<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get("/401", [AuthController::class, 'unauthorized'])->name('login');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post("validate", [AuthController::class, "validateToken"]);
    Route::post("logout", [AuthController::class, "logout"]);
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('me', [ProfileController::class, 'me']);
    });
});