<?php

use Illuminate\Support\Facades\Route;
use Modules\Contract\App\Http\Controllers\ContractController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('contracts', [ContractController::class, 'index']);
    Route::post('contracts', [ContractController::class, 'store']);
    Route::get('contracts/{contract}', [ContractController::class, 'show']);
    Route::put('contracts/{contract}', [ContractController::class, 'update']);
    Route::delete('contracts/{contract}', [ContractController::class, 'destroy']);
    Route::post('contracts/{contract}/submit', [ContractController::class, 'submit']);

    Route::post('contracts/{id}/send-to-contractor', [ContractController::class, 'sendToContractor']);
});
