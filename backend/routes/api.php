<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileMediaController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'application' => 'SocialX API',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [
        AuthController::class,
        'register',
    ]);

    Route::post('/login', [
        AuthController::class,
        'login',
    ]);

    Route::post('/forgot-password', [
        PasswordController::class,
        'forgotPassword',
    ])->middleware('throttle:5,1');

    Route::post('/reset-password', [
        PasswordController::class,
        'resetPassword',
    ]);

    Route::get('/auth/email/verify/{id}/{hash}', [
        EmailVerificationController::class,
        'verify',
    ])->middleware([
        'signed',
        'throttle:6,1',
    ])->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [
            AuthController::class,
            'me',
        ]);

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);

        Route::post('/email/verification-notification', [
            EmailVerificationController::class,
            'resend',
        ])->middleware('throttle:6,1');
    });
});

Route::get('/users/{username}', [
    UserProfileController::class,
    'show',
]);

Route::get('/users/{username}/followers', [
    FollowController::class,
    'followers',
]);

Route::get('/users/{username}/following', [
    FollowController::class,
    'following',
]);

Route::get('/posts/{post}', [
    PostController::class,
    'show',
]);

Route::get('/posts/{post}/thread', [
    PostController::class,
    'thread',
]);

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/profile', [
        ProfileController::class,
        'update',
    ]);

    Route::post('/profile/avatar', [
        ProfileMediaController::class,
        'updateAvatar',
    ]);

    Route::post('/profile/cover', [
        ProfileMediaController::class,
        'updateCover',
    ]);

    Route::post('/posts', [
        PostController::class,
        'store',
    ])->middleware(
        'permission:post.create'
    );

    Route::patch('/posts/{post}', [
        PostController::class,
        'update',
    ]);

    Route::delete('/posts/{post}', [
        PostController::class,
        'destroy',
    ]);

    Route::post('/posts/{post}/replies', [
        PostController::class,
        'reply',
    ])->middleware(
        'permission:post.create'
    );

    Route::post('/posts/{post}/like', [
        PostController::class,
        'like',
    ]);

    Route::delete('/posts/{post}/like', [
        PostController::class,
        'unlike',
    ]);

    Route::post('/posts/{post}/repost', [
        PostController::class,
        'repost',
    ]);

    Route::delete('/posts/{post}/repost', [
        PostController::class,
        'unrepost',
    ]);

    Route::post('/posts/{post}/bookmark', [
        PostController::class,
        'bookmark',
    ]);

    Route::delete('/posts/{post}/bookmark', [
        PostController::class,
        'unbookmark',
    ]);

    Route::get('/bookmarks', [
        BookmarkController::class,
        'index',
    ]);

    Route::post('/users/{user}/follow', [
        FollowController::class,
        'follow',
    ]);

    Route::delete('/users/{user}/follow', [
        FollowController::class,
        'unfollow',
    ]);

    Route::post('/follow-requests/{followRequest}/accept', [
        FollowController::class,
        'accept',
    ]);

    Route::delete('/follow-requests/{followRequest}/reject', [
        FollowController::class,
        'reject',
    ]);

    Route::get('/follow-requests', [
        FollowController::class,
        'requests',
    ]);
});
