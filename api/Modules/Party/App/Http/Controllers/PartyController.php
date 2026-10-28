<?php

namespace Modules\Party\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Party\App\Models\Party;
use Modules\Party\App\Http\Resources\PartyResource;

class PartyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Party::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $parties = $query->orderBy('name')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => PartyResource::collection($parties),
            'meta' => [
                'current_page' => $parties->currentPage(),
                'last_page' => $parties->lastPage(),
                'per_page' => $parties->perPage(),
                'total' => $parties->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => 'nullable|string|max:11|unique:parties',
            'registration_number' => 'nullable|string|max:50',
            'economic_code' => 'nullable|string|max:50',
            'type' => 'required|in:legal,real',
            'role' => 'required|in:employer,contractor,both',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:26',
        ]);

        $party = Party::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'طرف قرارداد با موفقیت ایجاد شد.',
            'data' => new PartyResource($party),
        ], 201);
    }

    public function show(Party $party): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PartyResource($party),
        ]);
    }

    public function update(Request $request, Party $party): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'national_id' => 'nullable|string|max:11|unique:parties,national_id,' . $party->id,
            'registration_number' => 'nullable|string|max:50',
            'economic_code' => 'nullable|string|max:50',
            'type' => 'sometimes|in:legal,real',
            'role' => 'sometimes|in:employer,contractor,both',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:26',
        ]);

        $party->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'طرف قرارداد با موفقیت بروزرسانی شد.',
            'data' => new PartyResource($party),
        ]);
    }

    public function destroy(Party $party): JsonResponse
    {
        if ($party->contracts()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'این طرف قرارداد دارای قرارداد است و قابل حذف نیست.',
            ], 400);
        }

        $party->delete();

        return response()->json([
            'success' => true,
            'message' => 'طرف قرارداد با موفقیت حذف شد.',
        ]);
    }
}
