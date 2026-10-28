<?php

namespace Modules\Contract\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contract\App\Services\ContractSettings;

/**
 * routes:
 *   Route::get('contract-settings', [ContractSettingController::class, 'show']);
 *   Route::put('contract-settings', [ContractSettingController::class, 'update']);
 *
 * تغییر آستانه روی قراردادهایی که اسلات امضای اجراییشان ساخته شده اثر نمی‌گذارد
 * (نقش‌ها موقع ساخت اسلات ثابت می‌شوند).
 */
class ContractSettingController extends Controller
{
    public function __construct(private ContractSettings $settings)
    {
    }

    public function show(): JsonResponse
    {
        $this->authorizeManage();

        return response()->json([
            'success' => true,
            'data' => ['executive_threshold_toman' => $this->settings->executiveThresholdToman()],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'executive_threshold_toman' => 'required|integer|min:1|max:1000000000000',
        ]);

        $this->settings->setExecutiveThresholdToman(
            (int) $validated['executive_threshold_toman'],
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'آستانه‌ی امضای اجرایی بروزرسانی شد.',
            'data' => ['executive_threshold_toman' => $this->settings->executiveThresholdToman()],
        ]);
    }

    private function authorizeManage(): void
    {
        abort_unless(
            auth()->user()?->can('contract-settings.manage'),
            403,
            'شما دسترسی لازم را ندارید.'
        );
    }
}
