<?php

use App\Http\Controllers\CommunityController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get("/401", [AuthController::class, 'unauthorized'])->name('login');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post("validate", [AuthController::class, "validateToken"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::get('google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('me', [ProfileController::class, 'me']);
    });
});

Route::prefix('communities')->group(function () {
    Route::get('', [CommunityController::class, 'index']);
    Route::get('{communityId}', [CommunityController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('', [CommunityController::class, 'store']);
        Route::delete('{communityId}', [CommunityController::class, 'delete']);
        Route::post('join', [CommunityController::class, 'join']);
        Route::post('leave', [CommunityController::class, 'leave']);
    });
});