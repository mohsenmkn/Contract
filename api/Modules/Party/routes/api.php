
<?php

use Illuminate\Support\Facades\Route;
use Modules\Party\App\Http\Controllers\PartyController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('parties', PartyController::class);
});
