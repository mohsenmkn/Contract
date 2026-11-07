<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleDisplayNameSeeder extends Seeder
{
    /**
     * Set Persian labels for the application's known system roles.
     * The role `name` values are intentionally never changed.
     */
    public function run(): void
    {
        $labels = [
            'super-admin' => 'مدیر کل سامانه',
            'super-admin' => 'مدیر ارشد سامانه',
            'admin' => 'مدیر سامانه',
            'viewer' => 'مشاهده‌گر',
            'hr_manager' => 'مدیر منابع انسانی',
            'employee' => 'کارمند',
            'contract_manager' => 'مدیر قراردادها',
            'legal_expert' => 'کارشناس حقوقی',
            'financial_expert' => 'کارشناس مالی',
            'contractor' => 'پیمانکار',
            'unit_representative' => 'نماینده واحد',
            'unit_contract_staff' => 'کارشناس قراردادهای واحد',
            'unit_head' => 'رئیس واحد',
            'unit_manager' => 'مدیر واحد',
            'unit_deputy' => 'معاون واحد',
            'legal_officer' => 'مسئول حقوقی قراردادها',
            'guarantee_officer' => 'مسئول دریافت ضمانت',
            'ceo' => 'مدیرعامل',
            'deputy_ceo' => 'معاون مالی',
            'board_member' => 'عضو هیئت‌مدیره',
        ];

        $updated = 0;
        $missing = [];

        foreach ($labels as $systemName => $displayName) {
            $roles = Role::query()->where('name', $systemName)->get();

            if ($roles->isEmpty()) {
                $missing[] = $systemName;
                continue;
            }

            foreach ($roles as $role) {
                $role->forceFill(['display_name' => $displayName])->save();
                $updated++;
            }
        }

        $this->command?->info("برچسب فارسی {$updated} نقش ثبت شد؛ نام‌های سیستمی بدون تغییر ماندند.");

        if ($missing !== []) {
            $this->command?->warn('نقش‌های موجود نبودند و رد شدند: ' . implode(', ', $missing));
        }
    }
}
