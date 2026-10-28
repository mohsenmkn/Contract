<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Auth\App\Http\Controllers\AuthController;

Route::prefix('v1/auth')->group(function() {
    // روت‌های عمومی (بدون احراز هویت)
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);
    Route::get('/captcha', [AuthController::class, 'getCaptcha']);



    // روت‌های محافظت‌شده
    Route::middleware(['auth:sanctum', 'user.can_login'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);


        // پروفایل
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
        Route::put('/password', [AuthController::class, 'changePassword']);

        // تنظیمات
        Route::get('/settings', [AuthController::class, 'getSettings']);
        Route::put('/settings', [AuthController::class, 'updateSettings']);

        // دستگاه‌ها و نشست‌ها
        Route::get('/sessions', [AuthController::class, 'sessions']);
        Route::delete('/sessions/{tokenId}', [AuthController::class, 'revokeSession']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);



        // Roles and user access are managed by the Acl and User APIs.
    });
});
