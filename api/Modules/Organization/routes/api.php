<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\App\Http\Controllers\UnitController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('organizational-units', [UnitController::class, 'index']);
    Route::post('organizational-units', [UnitController::class, 'store']);
    Route::get('organizational-units/{id}', [UnitController::class, 'show']);
    Route::put('organizational-units/{id}', [UnitController::class, 'update']);
    Route::delete('organizational-units/{id}', [UnitController::class, 'destroy']);

    Route::get('organizational-units/{id}/representative', [UnitController::class, 'getRepresentative']);
    Route::post('organizational-units/{id}/representative', [UnitController::class, 'setRepresentative']);
    Route::delete('organizational-units/{id}/representative', [UnitController::class, 'removeRepresentative']);

    Route::get('organizational-units/my-unit', [UnitController::class, 'myUnit']);
});
