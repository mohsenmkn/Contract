<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoice\App\Http\Controllers\InvoiceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('contracts/{contract}/invoices', [InvoiceController::class, 'index']);
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('invoices/{invoice}/submit', [InvoiceController::class, 'submit']);
    Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve']);
});
