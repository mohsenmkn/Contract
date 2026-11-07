<?php

namespace Modules\Organization\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\App\Models\OrganizationalUnit;
use Modules\Organization\App\Models\UnitRepresentative;
use Modules\Auth\App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UnitController extends Controller
{
    /**
     * لیست واحدها
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrganizationalUnit::with(['representative.user', 'contractSigners']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $units = $query->orderBy('name')->paginate($request->get('per_page', 15));
        $items = collect($units->items())->map(function (OrganizationalUnit $unit) {
            $unit->setAttribute('contract_signers', $unit->contractSigners->map(fn (User $user) => [
                'user_id' => $user->id,
                'unit_role' => $user->pivot->unit_role,
                'name' => $user->name,
                'mobile' => $user->mobile,
            ])->values()->all());
            $unit->unsetRelation('contractSigners');

            return $unit;
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
        ]);
    }

    /**
     * ایجاد واحد جدید
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:organizational_units,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $unit = OrganizationalUnit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'واحد با موفقیت ایجاد شد.',
            'data' => $unit->load(['representative.user']),
        ], 201);
    }

    /**
     * نمایش جزئیات واحد
     */
    public function show($id): JsonResponse
    {
        $unit = OrganizationalUnit::with(['representative.user', 'contracts'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $unit,
        ]);
    }

    /**
     * بروزرسانی واحد
     */
    public function update(Request $request, $id): JsonResponse
    {
        $unit = OrganizationalUnit::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:organizational_units,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'واحد با موفقیت بروزرسانی شد.',
            'data' => $unit->fresh(['representative.user']),
        ]);
    }

    /**
     * حذف واحد
     */
    public function destroy($id): JsonResponse
    {
        $unit = OrganizationalUnit::findOrFail($id);
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'واحد با موفقیت حذف شد.',
        ]);
    }

    /**
     * دریافت نماینده واحد
     */
    public function getRepresentative($id): JsonResponse
    {
        $unit = OrganizationalUnit::findOrFail($id);
        $representative = $unit->representative()->with('user')->first();

        return response()->json([
            'success' => true,
            'data' => $representative,
        ]);
    }

    /**
     * تعیین/بروزرسانی نماینده واحد
     */
    public function setRepresentative(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $unit = OrganizationalUnit::findOrFail($id);

        // بررسی اینکه کاربر قبلاً نماینده واحد دیگری نیست
        $existingRep = UnitRepresentative::where('user_id', $validated['user_id'])
            ->where('is_active', true)
            ->first();

        if ($existingRep && $existingRep->unit_id !== $id) {
            return response()->json([
                'success' => false,
                'message' => 'این کاربر قبلاً نماینده واحد دیگری است.',
            ], 400);
        }

        DB::transaction(function () use ($unit, $validated) {
            // غیرفعال کردن نماینده قبلی
            UnitRepresentative::where('unit_id', $unit->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // ایجاد نماینده جدید
            UnitRepresentative::updateOrCreate(
                ['unit_id' => $unit->id],
                [
                    'user_id' => $validated['user_id'],
                    'is_active' => true,
                    'assigned_at' => now(),
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'نماینده با موفقیت تعیین شد.',
            'data' => $unit->fresh(['representative.user']),
        ]);
    }

    /**
     * حذف نماینده واحد
     */
    public function removeRepresentative($id): JsonResponse
    {
        $unit = OrganizationalUnit::findOrFail($id);

        UnitRepresentative::where('unit_id', $unit->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'نماینده با موفقیت حذف شد.',
        ]);
    }

    /**
     * دریافت واحد کاربر فعلی
     */
    public function myUnit(): JsonResponse
    {
        $user = auth()->user();
        $representative = UnitRepresentative::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('unit')
            ->first();

        if (!$representative) {
            $unitId = DB::table('contract_unit_signers')->where('user_id', $user->id)->value('unit_id');
            $unit = $unitId ? OrganizationalUnit::find($unitId) : null;
            if ($unit) {
                return response()->json(['success' => true, 'data' => $unit]);
            }

            return response()->json([
                'success' => false,
                'message' => 'شما نماینده هیچ واحدی نیستید.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $representative->unit,
        ]);
    }

    /** Assign an existing employee to sign contracts for this unit. */
    public function assignContractSigner(Request $request, $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('units.assign_representative'), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'unit_role' => 'required|in:unit_contract_staff,unit_head,unit_manager,unit_deputy',
        ]);

        $unit = OrganizationalUnit::findOrFail($id);
        $user = User::findOrFail($validated['user_id']);
        abort_if($user->isContractor(), 422, 'پیمانکار را نمی‌توان به عنوان امضاکننده داخلی واحد تعیین کرد.');

        DB::transaction(function () use ($unit, $user, $validated) {
            DB::table('contract_unit_signers')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'unit_id' => $unit->id,
                    'unit_role' => $validated['unit_role'],
                    'assigned_by' => auth()->id(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $role = Role::firstOrCreate([
                'name' => $validated['unit_role'],
                'guard_name' => config('rbac.guard', 'web'),
            ]);
            $this->ensureUnitRolePermissions($role, $validated['unit_role']);
            $user->assignRole($role);
        });

        return response()->json([
            'success' => true,
            'message' => 'امضاکننده واحد ثبت شد.',
            'data' => [
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'unit_role' => $validated['unit_role'],
            ],
        ]);
    }

    /** Return the four configured contract roles for a unit. */
    public function getContractSigners($id): JsonResponse
    {
        abort_unless(auth()->user()?->can('units.read'), 403);

        $unit = OrganizationalUnit::findOrFail($id);
        $signers = DB::table('contract_unit_signers')
            ->join('users', 'users.id', '=', 'contract_unit_signers.user_id')
            ->where('contract_unit_signers.unit_id', $unit->id)
            ->orderBy('contract_unit_signers.unit_role')
            ->get([
                'contract_unit_signers.user_id',
                'contract_unit_signers.unit_role',
                'users.name',
                'users.mobile',
            ])
            ->keyBy('unit_role');

        return response()->json(['success' => true, 'data' => $signers]);
    }

    /** Replace the unit's coordinator/head/manager/deputy assignments as one operation. */
    public function syncContractSigners(Request $request, $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('units.assign_representative'), 403);

        $roles = ['unit_contract_staff', 'unit_head', 'unit_manager', 'unit_deputy'];
        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.unit_contract_staff' => ['present', 'nullable', 'integer', 'exists:users,id'],
            'assignments.unit_head' => ['present', 'nullable', 'integer', 'exists:users,id'],
            'assignments.unit_manager' => ['present', 'nullable', 'integer', 'exists:users,id'],
            'assignments.unit_deputy' => ['present', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $unit = OrganizationalUnit::findOrFail($id);
        $assignments = $validated['assignments'];
        $userIds = array_values(array_filter($assignments));

        if (count($userIds) !== count(array_unique($userIds))) {
            return response()->json(['success' => false, 'message' => 'برای هر سمت باید کاربر جداگانه انتخاب شود.'], 422);
        }

        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        foreach ($users as $user) {
            if ($user->isContractor()) {
                return response()->json(['success' => false, 'message' => 'پیمانکار را نمی‌توان برای سمت‌های داخلی واحد انتخاب کرد.'], 422);
            }
        }

        $assignedElsewhere = DB::table('contract_unit_signers')
            ->whereIn('user_id', $userIds)
            ->where('unit_id', '!=', $unit->id)
            ->exists();
        if ($assignedElsewhere) {
            return response()->json(['success' => false, 'message' => 'یکی از کاربران انتخاب‌شده به واحد دیگری تخصیص دارد.'], 422);
        }

        DB::transaction(function () use ($unit, $assignments, $roles, $users) {
            DB::table('contract_unit_signers')->where('unit_id', $unit->id)->delete();

            foreach ($roles as $unitRole) {
                $userId = $assignments[$unitRole] ?? null;
                if (!$userId) {
                    continue;
                }

                DB::table('contract_unit_signers')->insert([
                    'unit_id' => $unit->id,
                    'user_id' => $userId,
                    'unit_role' => $unitRole,
                    'assigned_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $role = Role::firstOrCreate([
                    'name' => $unitRole,
                    'guard_name' => config('rbac.guard', 'web'),
                ]);
                $this->ensureUnitRolePermissions($role, $unitRole);
                $users->get($userId)?->assignRole($role);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'مسئولان واحد ذخیره شدند.',
            'data' => $this->contractSignerAssignments($unit->id),
        ]);
    }

    private function ensureUnitRolePermissions(Role $role, string $unitRole): void
    {
        $permissionNames = config("rbac.default_roles.{$unitRole}.permissions", []);
        if ($permissionNames === '*' || $permissionNames === []) {
            return;
        }

        $permissions = collect($permissionNames)->map(fn (string $name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => $role->guard_name,
        ]));

        if ($permissions->isNotEmpty()) $role->givePermissionTo($permissions);
    }

    private function contractSignerAssignments(int $unitId): array
    {
        return DB::table('contract_unit_signers')
            ->join('users', 'users.id', '=', 'contract_unit_signers.user_id')
            ->where('contract_unit_signers.unit_id', $unitId)
            ->get([
                'contract_unit_signers.user_id',
                'contract_unit_signers.unit_role',
                'users.name',
                'users.mobile',
            ])
            ->keyBy('unit_role')
            ->all();
    }
}
