<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Dashboard\App\Http\Controllers\DashboardController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/dashboard/contract-stats', [DashboardController::class, 'contractStats']);
    Route::get('/dashboard/invoice-stats', [DashboardController::class, 'invoiceStats']);
    Route::get('/dashboard/contract-status-chart', [DashboardController::class, 'contractStatusChart']);
    Route::get('/dashboard/contract-amount-chart', [DashboardController::class, 'contractAmountChart']);
    Route::get('/dashboard/expiring-contracts', [DashboardController::class, 'expiringContracts']);
    Route::get('/dashboard/expiring-guarantees', [DashboardController::class, 'expiringGuarantees']);
    Route::get('/dashboard/contractor', [DashboardController::class, 'contractorDashboard']);
    Route::get('/dashboard/recent-activities', [DashboardController::class, 'recentActivities']);

});
