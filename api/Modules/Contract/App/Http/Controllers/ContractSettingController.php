<?php

namespace Modules\Contract\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
            'data' => [
                'has_letterhead' => $this->hasLetterhead(),
                'expiry_alerts' => $this->settings->expiryAlertSettings(),
            ],
        ]);
    }

    public function letterhead(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['has_letterhead' => $this->hasLetterhead()]]);
    }

    public function letterheadImage(): BinaryFileResponse
    {
        $path = $this->settings->letterheadPath();
        $disk = Storage::disk('public');
        abort_unless($path && $disk->exists($path), 404, 'سربرگ ثبت نشده است.');

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function uploadLetterhead(Request $request): JsonResponse
    {
        $this->authorizeManage();
        $validated = $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $disk = Storage::disk('public');
        $oldPath = $this->settings->letterheadPath();
        $path = $validated['image']->store('contract-settings', 'public');
        abort_unless($path, 500, 'ذخیره فایل سربرگ انجام نشد.');
        $this->settings->setLetterheadPath($path, auth()->user());

        if ($oldPath && $oldPath !== $path) {
            $disk->delete($oldPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'سربرگ قرارداد ذخیره شد.',
            'data' => ['has_letterhead' => true],
        ], 201);
    }

    public function deleteLetterhead(): JsonResponse
    {
        $this->authorizeManage();
        $path = $this->settings->letterheadPath();
        $this->settings->setLetterheadPath(null, auth()->user());

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['success' => true, 'message' => 'سربرگ چاپ حذف شد.']);
    }

    public function updateExpiryAlerts(Request $request): JsonResponse
    {
        $this->authorizeManage();
        $validated = $request->validate([
            'days_before' => ['required', 'array', 'min:1', 'max:10'],
            'days_before.*' => ['required', 'integer', 'min:1', 'max:365'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'in:in_app,email,sms'],
            'phone_numbers' => [Rule::requiredIf(fn () => in_array('sms', $request->input('channels', []), true)), 'array'],
            'phone_numbers.*' => ['required', 'regex:/^\\+?[0-9]{7,15}$/'],
            'email_addresses' => [Rule::requiredIf(fn () => in_array('email', $request->input('channels', []), true)), 'array'],
            'email_addresses.*' => ['required', 'email:rfc', 'max:254'],
            'send_time' => ['required', 'date_format:H:i'],
        ]);
        $settings = [
            'days_before' => collect($validated['days_before'])->map(fn ($day) => (int) $day)->unique()->sortDesc()->values()->all(),
            'channels' => array_values(array_unique($validated['channels'])),
            'phone_numbers' => array_values(array_unique($validated['phone_numbers'] ?? [])),
            'email_addresses' => array_values(array_unique($validated['email_addresses'] ?? [])),
            'send_time' => $validated['send_time'],
        ];
        DB::transaction(function () use ($settings) {
            $this->settings->setExpiryAlertSettings($settings, auth()->user());
            app(\Modules\Alert\App\Services\AlertSchedulerService::class)->rescheduleExpiringContracts($settings['days_before']);
        });
        return response()->json(['success' => true, 'message' => 'تنظیمات هشدارهای انقضا ذخیره شد.', 'data' => $settings]);
    }

    private function hasLetterhead(): bool
    {
        $path = $this->settings->letterheadPath();

        return (bool) ($path && Storage::disk('public')->exists($path));
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
