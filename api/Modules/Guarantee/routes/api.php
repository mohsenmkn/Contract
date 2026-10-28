<?php

use Illuminate\Support\Facades\Route;
use Modules\Guarantee\App\Http\Controllers\GuaranteeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('contracts/{contract}/guarantees', [GuaranteeController::class, 'index']);
    Route::post('guarantees', [GuaranteeController::class, 'store']);
    Route::get('guarantees/{guarantee}', [GuaranteeController::class, 'show']);
    Route::post('guarantees/{guarantee}/release', [GuaranteeController::class, 'release']);
});
