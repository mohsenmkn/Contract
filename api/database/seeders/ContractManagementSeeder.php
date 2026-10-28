<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ContractManagementSeeder extends Seeder
{
    public function run(): void
    {
        // ریست کردن کش
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ایجاد دسترسی‌ها از فایل config
        $config = config('rbac');
        $allPermissions = [];

        foreach ($config['modules'] as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$module}.{$action}";
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $allPermissions[$permissionName] = $permission;
            }
        }

        // ایجاد نقش‌های پیش‌فرض
        foreach ($config['default_roles'] as $roleKey => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleKey],
                ['guard_name' => $config['guard']]
            );

            // اگر دسترسی * باشد، تمام دسترسی‌ها را بده
            if ($roleData['permissions'] === '*') {
                $role->syncPermissions(array_values($allPermissions));
            } else {
                $permissions = [];
                foreach ($roleData['permissions'] as $permName) {
                    if (isset($allPermissions[$permName])) {
                        $permissions[] = $allPermissions[$permName];
                    }
                }
                $role->syncPermissions($permissions);
            }
        }

        $this->command->info('✅ دسترسی‌ها و نقش‌های سامانه مدیریت قراردادها ایجاد شدند.');
    }
}
