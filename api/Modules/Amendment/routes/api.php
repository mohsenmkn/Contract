<?php

use Illuminate\Support\Facades\Route;
use Modules\Amendment\App\Http\Controllers\AmendmentController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('contracts/{contract}/amendments', [AmendmentController::class, 'index']);
    Route::post('amendments', [AmendmentController::class, 'store']);
    Route::get('amendments/{amendment}', [AmendmentController::class, 'show']);
    Route::post('amendments/{amendment}/approve', [AmendmentController::class, 'approve']);
});
