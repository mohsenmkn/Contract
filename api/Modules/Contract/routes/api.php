<?php

use Illuminate\Support\Facades\Route;
use Modules\Contract\App\Http\Controllers\ContractController;
use Modules\Contract\App\Http\Controllers\ContractSettingController;
use Modules\Contract\App\Http\Controllers\GuaranteeDeskController;
use Modules\Contract\App\Http\Controllers\NotificationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('contracts', [ContractController::class, 'index']);
    Route::post('contracts', [ContractController::class, 'store']);
    Route::get('contracts/{contract}', [ContractController::class, 'show']);
    Route::put('contracts/{contract}', [ContractController::class, 'update']);
    Route::delete('contracts/{contract}', [ContractController::class, 'destroy']);
    //Route::post('contracts/{contract}/submit', [ContractController::class, 'submit']);

    // Workflow Routes
    Route::post('contracts/{id}/submit-to-legal', [ContractController::class, 'submitToLegal']);
    Route::post('contracts/{id}/submit-to-contract-review', [ContractController::class, 'submitToContractReview']);
    Route::post('contracts/{id}/legal-draft', [ContractController::class, 'legalDraft']);
    Route::put('contracts/{id}/guarantee-requirement', [ContractController::class, 'updateGuaranteeRequirement']);
    Route::post('contracts/{id}/return-to-requester', [ContractController::class, 'returnToRequester']);
    Route::post('contracts/{id}/send-to-contractor', [ContractController::class, 'sendToContractor']);
    Route::get('contracts/{id}/can-sign', [ContractController::class, 'canSign']);
    Route::post('contracts/{id}/notify', [ContractController::class, 'notify']);
    Route::get('contracts/{id}/workflow-history', [ContractController::class, 'workflowHistory']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Guarantee desk sees only the limited receipt view and can confirm collected instruments.
    Route::get('guarantee-desk', [GuaranteeDeskController::class, 'index']);
    Route::get('guarantee-desk/{id}', [GuaranteeDeskController::class, 'show']);
    Route::post('guarantee-desk/{id}/confirm', [GuaranteeDeskController::class, 'confirm']);

    Route::get('contract-settings', [ContractSettingController::class, 'show']);
    Route::put('contract-settings/expiry-alerts', [ContractSettingController::class, 'updateExpiryAlerts']);
    Route::get('contract-settings/letterhead', [ContractSettingController::class, 'letterhead']);
    Route::get('contract-settings/letterhead/image', [ContractSettingController::class, 'letterheadImage']);
    Route::post('contract-settings/letterhead', [ContractSettingController::class, 'uploadLetterhead']);
    Route::delete('contract-settings/letterhead', [ContractSettingController::class, 'deleteLetterhead']);
});
