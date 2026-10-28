<?php

use Illuminate\Support\Facades\Route;
use Modules\Signature\App\Http\Controllers\SignatureController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('signatures/pending', [SignatureController::class, 'myPendingSignatures']);
    Route::get('signatures/{signature}', [SignatureController::class, 'show']);
    Route::post('signatures/{signature}/request-otp', [SignatureController::class, 'requestOtp']);
    Route::post('signatures/{signature}/verify-and-sign', [SignatureController::class, 'verifyAndSign']);
    Route::post('signatures/{signature}/reject', [SignatureController::class, 'rejectDocument']);
});
