<?php

namespace Modules\User\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\Auth\App\Models\User;
use Modules\Auth\App\Resources\UserResource;
use Modules\Party\App\Models\Party;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['roles', 'party'])
            ->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('national_code', 'like', "%{$search}%")
                    ->orWhere('personnel_code', 'like', "%{$search}%");
            });
        }

        if ($employeeType = $request->get('employee_type')) {
            $query->where('employee_type', $employeeType);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'national_code' => ['nullable', 'string', 'max:20', 'unique:users,national_code'],
            'personnel_code' => ['nullable', 'string', 'max:50', 'unique:users,personnel_code'],
            'employee_type' => ['required', 'in:personnel,contractor'],
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        // ✅ اعتبارسنجی اضافی برای پیمانکاران
        if ($validated['employee_type'] === 'contractor') {
            if (empty($validated['party_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'پیمانکار باید به یک شرکت متصل باشد.',
                    'errors' => ['party_id' => ['انتخاب شرکت پیمانکاری الزامی است']]
                ], 422);
            }

            $party = Party::where('id', $validated['party_id'])
                ->where('role', 'contractor')
                ->first();

            if (!$party) {
                return response()->json([
                    'success' => false,
                    'message' => 'شرکت پیمانکاری نامعتبر است.',
                    'errors' => ['party_id' => ['شرکت انتخاب‌شده معتبر نیست یا نقش آن contractor نیست']]
                ], 422);
            }

            if ($party->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'این شرکت قبلاً به کاربر دیگری متصل شده است.',
                    'errors' => ['party_id' => ['این شرکت قبلاً به کاربر دیگری متصل شده است']]
                ], 422);
            }
        }

        $user = null;
        DB::transaction(function () use ($validated, &$user) {
            $user = User::create([
                'name' => $validated['name'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'national_code' => $validated['national_code'] ?? null,
                'personnel_code' => $validated['personnel_code'] ?? null,
                'employee_type' => $validated['employee_type'],
                'party_id' => $validated['party_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }

            // ✅ اتصال Party به کاربر
            if ($user->party_id) {
                Party::where('id', $user->party_id)->update(['user_id' => $user->id]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت ایجاد شد.',
            'data' => new UserResource($user->load(['roles', 'party']))
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'national_code' => ['nullable', 'string', 'max:20', Rule::unique('users', 'national_code')->ignore($user->id)],
            'personnel_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'personnel_code')->ignore($user->id)],
            'employee_type' => ['required', 'in:personnel,contractor'],
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        // ✅ اعتبارسنجی برای پیمانکاران
        if ($validated['employee_type'] === 'contractor') {
            if (empty($validated['party_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'پیمانکار باید به یک شرکت متصل باشد.',
                    'errors' => ['party_id' => ['انتخاب شرکت پیمانکاری الزامی است']]
                ], 422);
            }

            $party = Party::where('id', $validated['party_id'])
                ->where('role', 'contractor')
                ->first();

            if (!$party) {
                return response()->json([
                    'success' => false,
                    'message' => 'شرکت پیمانکاری نامعتبر است.',
                    'errors' => ['party_id' => ['شرکت انتخاب‌شده معتبر نیست']]
                ], 422);
            }

            // اگر Party تغییر کرده و به کاربر دیگری متصل است
            if ($party->user_id && $party->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'این شرکت قبلاً به کاربر دیگری متصل شده است.',
                    'errors' => ['party_id' => ['این شرکت قبلاً به کاربر دیگری متصل شده است']]
                ], 422);
            }
        }

        DB::transaction(function () use ($validated, $user) {
            // تغییر نوع از contractor به personnel
            if ($user->employee_type === 'contractor' && $validated['employee_type'] === 'personnel') {
                if ($user->party_id) {
                    Party::where('id', $user->party_id)->update(['user_id' => null]);
                }
                $validated['party_id'] = null;
            }

            // تغییر party_id
            if (($user->party_id ?? null) !== ($validated['party_id'] ?? null)) {
                // قطع ارتباط با Party قبلی
                if ($user->party_id) {
                    Party::where('id', $user->party_id)->update(['user_id' => null]);
                }

                // اتصال به Party جدید
                if (!empty($validated['party_id'])) {
                    Party::where('id', $validated['party_id'])->update(['user_id' => $user->id]);
                }
            }

            $updateData = [
                'name' => $validated['name'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'] ?? null,
                'national_code' => $validated['national_code'] ?? null,
                'personnel_code' => $validated['personnel_code'] ?? null,
                'employee_type' => $validated['employee_type'],
                'party_id' => $validated['party_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            if (isset($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت بروزرسانی شد.',
            'data' => new UserResource($user->load(['roles', 'party']))
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->party_id) {
            Party::where('id', $user->party_id)->update(['user_id' => null]);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت حذف شد.',
        ]);
    }
}
