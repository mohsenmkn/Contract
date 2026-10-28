<?php

namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\App\Models\User;

/**
 * تنظیمات قابل تغییر توسط مدیر سیستم (جدول contract_settings).
 * اگر ردیفی وجود نداشت، مقدار پیش‌فرض از config/signing.php خوانده می‌شود.
 */
class ContractSettings
{
    private const KEY_THRESHOLD = 'executive_threshold_toman';
    private const CACHE_KEY = 'contract_settings.executive_threshold_toman';

    /** آستانه‌ی امضای عالی (تومان) */
    public function executiveThresholdToman(): int
    {
        return (int) Cache::remember(self::CACHE_KEY, 300, function () {
            $raw = DB::table('contract_settings')->where('key', self::KEY_THRESHOLD)->value('value');

            return $raw !== null
                ? (int) json_decode($raw)
                : (int) config('signing.executive_threshold_toman');
        });
    }

    public function setExecutiveThresholdToman(int $value, ?User $by = null): void
    {
        $old = $this->executiveThresholdToman();

        DB::table('contract_settings')->updateOrInsert(
            ['key' => self::KEY_THRESHOLD],
            ['value' => json_encode($value), 'updated_by' => $by?->id, 'updated_at' => now()]
        );

        Cache::forget(self::CACHE_KEY);

        // تغییر آستانه تعیین می‌کند چه کسی امضا کند؛ ردپا بماند
        Log::info('contract setting changed', [
            'key' => self::KEY_THRESHOLD,
            'old' => $old,
            'new' => $value,
            'user_id' => $by?->id,
        ]);
    }
}
