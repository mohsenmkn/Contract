<?php

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\App\Models\OrganizationalUnit;
use Modules\Organization\App\Models\UnitRepresentative;
use Modules\Auth\App\Models\User;
use Spatie\Permission\Models\Role;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // ایجاد واحدهای سازمانی پیش‌فرض
        $units = [
            [
                'name' => 'واحد فناوری اطلاعات',
                'code' => 'IT',
                'description' => 'مسئول زیرساخت و توسعه نرم‌افزار',
            ],
            [
                'name' => 'واحد مالی',
                'code' => 'FIN',
                'description' => 'مسئول امور مالی و حسابداری',
            ],
            [
                'name' => 'واحد حقوقی و قراردادها',
                'code' => 'LEGAL',
                'description' => 'تنظیم و بررسی حقوقی قراردادها',
            ],
            [
                'name' => 'واحد منابع انسانی',
                'code' => 'HR',
                'description' => 'مدیریت منابع انسانی',
            ],
            [
                'name' => 'واحد بازرگانی',
                'code' => 'COMM',
                'description' => 'امور بازرگانی و فروش',
            ],
        ];

        foreach ($units as $unitData) {
            OrganizationalUnit::firstOrCreate(
                ['code' => $unitData['code']],
                $unitData
            );
        }

        // ایجاد نقش‌های جدید
        $roles = [
            'unit_representative' => [
                'name' => 'نماینده واحد',
                'description' => 'نماینده یک واحد سازمانی برای درخواست قرارداد',
            ],
            'legal_officer' => [
                'name' => 'کارشناس حقوقی',
                'description' => 'تنظیم و بررسی حقوقی قراردادها',
            ],
            'ceo' => [
                'name' => 'مدیرعامل',
                'description' => 'امضای نهایی قراردادها',
            ],
            'deputy_ceo' => [
                'name' => 'معاون مالی',
                'description' => 'امضای قراردادهای زیر 900 میلیون تومان',
            ],
            'board_member' => [
                'name' => 'عضو هیئت مدیره',
                'description' => 'امضای قراردادها به عنوان عضو هیئت مدیره',
            ],
        ];

        foreach ($roles as $roleName => $roleData) {
            Role::firstOrCreate(
                ['name' => $roleName],
                $roleData
            );
        }

        $this->command->info('واحدهای سازمانی و نقش‌ها با موفقیت ایجاد شدند.');
    }
}
