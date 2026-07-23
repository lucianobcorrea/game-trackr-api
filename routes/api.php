<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('validate', [AuthController::class, 'validateToken']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('verify-reset-code', [AuthController::class, 'verifyResetCode']);

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

    Route::middleware('auth:api')->group(function () {
        Route::post('', [CommunityController::class, 'store']);
        Route::get('joined', [CommunityController::class, 'joined']);
    });

    Route::get('{communityId}', [CommunityController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::delete('{communityId}', [CommunityController::class, 'delete']);
        Route::post('join/{communityId}', [CommunityController::class, 'join']);
        Route::post('leave/{communityId}', [CommunityController::class, 'leave']);
    });
});

Route::prefix('posts')->group(function () {
    Route::get('', [PostController::class, 'index']);
    Route::get('{postId}', [PostController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('', [PostController::class, 'store']);
        Route::delete('{postId}', [PostController::class, 'delete']);
        Route::post('{postId}/comment', [PostController::class, 'comment']);
        Route::post('{postId}/comment/{commentId}/reply', [PostController::class, 'commentReply']);
        Route::post('{postId}/like', [PostController::class, 'like']);
        Route::post('{postId}/comment/{commentId}/like', [PostController::class, 'likeComment']);
    });
});
