<?php

namespace Modules\Organization\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\App\Models\OrganizationalUnit;
use Modules\Organization\App\Models\UnitRepresentative;
use Modules\Auth\App\Models\User;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    /**
     * لیست واحدها
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrganizationalUnit::with(['representative.user']);

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

        return response()->json([
            'success' => true,
            'data' => $units->items(),
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
}
