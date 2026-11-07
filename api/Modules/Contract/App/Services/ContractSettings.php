<?php

namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\App\Models\User;

class ContractSettings
{
    private const KEY_LETTERHEAD_PATH = 'print_letterhead_path';
    private const KEY_EXPIRY_ALERTS = 'contract_expiry_alerts';

    public function expiryAlertSettings(): array
    {
        $value = DB::table('contract_settings')->where('key', self::KEY_EXPIRY_ALERTS)->value('value');
        $settings = is_string($value) ? json_decode($value, true) : null;
        return [
            'days_before' => collect($settings['days_before'] ?? [30, 7, 1])->map(fn ($day) => (int) $day)
                ->filter(fn ($day) => $day >= 1 && $day <= 365)->unique()->sortDesc()->values()->all(),
            'channels' => array_values(array_intersect($settings['channels'] ?? ['in_app', 'email', 'sms'], ['in_app', 'email', 'sms'])),
            'phone_numbers' => array_values(array_filter($settings['phone_numbers'] ?? [], fn ($value) => is_string($value) && $value !== '')),
            'email_addresses' => array_values(array_filter($settings['email_addresses'] ?? [], fn ($value) => is_string($value) && $value !== '')),
            'send_time' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $settings['send_time'] ?? '')
                ? $settings['send_time']
                : '08:00',
        ];
    }

    public function setExpiryAlertSettings(array $settings, ?User $by = null): void
    {
        DB::table('contract_settings')->updateOrInsert(
            ['key' => self::KEY_EXPIRY_ALERTS],
            ['value' => json_encode($settings, JSON_UNESCAPED_UNICODE), 'updated_by' => $by?->id, 'updated_at' => now()]
        );
    }

    public function letterheadPath(): ?string
    {
        $path = DB::table('contract_settings')->where('key', self::KEY_LETTERHEAD_PATH)->value('value');

        return $path !== null ? (string) json_decode($path) : null;
    }

    public function setLetterheadPath(?string $path, ?User $by = null): void
    {
        if ($path === null) {
            DB::table('contract_settings')->where('key', self::KEY_LETTERHEAD_PATH)->delete();
            return;
        }

        DB::table('contract_settings')->updateOrInsert(
            ['key' => self::KEY_LETTERHEAD_PATH],
            ['value' => json_encode($path), 'updated_by' => $by?->id, 'updated_at' => now()]
        );
    }
}
