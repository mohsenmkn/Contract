<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\App\Http\Controllers\PaymentController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // لیست تمام پرداخت‌ها (جدید)
    Route::get('payments', [PaymentController::class, 'index']);

    // لیست پرداخت‌های یک صورت‌وضعیت
    Route::get('invoices/{invoice}/payments', [PaymentController::class, 'byInvoice']);

    // ثبت پرداخت
    Route::post('payments', [PaymentController::class, 'store']);

    // نمایش جزئیات
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
});
