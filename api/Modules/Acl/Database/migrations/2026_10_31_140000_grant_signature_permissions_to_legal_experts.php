<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('rbac.guard', 'web');
        $legalRole = Role::query()
            ->where('name', 'legal_expert')
            ->where('guard_name', $guard)
            ->first();

        if (!$legalRole) {
            return;
        }

        $permissions = collect(['signatures.read', 'signatures.sign'])
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]));

        $legalRole->givePermissionTo($permissions);
    }

    public function down(): void
    {
        // Keep role permissions intact during rollback; they may have been assigned independently.
    }
};
