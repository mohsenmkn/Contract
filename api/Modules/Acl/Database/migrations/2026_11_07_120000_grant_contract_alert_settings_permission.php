<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('rbac.guard', 'web');
        $permission = Permission::firstOrCreate([
            'name' => 'contract-settings.manage',
            'guard_name' => $guard,
        ]);

        foreach (['contract_manager', 'legal_expert'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $guard = config('rbac.guard', 'web');
        $permission = Permission::query()->where('name', 'contract-settings.manage')->where('guard_name', $guard)->first();
        if ($permission) {
            Role::query()->whereIn('name', ['contract_manager', 'legal_expert'])
                ->where('guard_name', $guard)->get()->each(fn (Role $role) => $role->revokePermissionTo($permission));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
