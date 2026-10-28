<?php

namespace Modules\Contract\App\Http\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Services\GuaranteeGateService;

/**
 * میز کار کارشناس مالی ضمانت: فقط نمای محدود قرارداد (بدون متن، امضاها و تاریخچه).
 *
 * routes (داخل گروه auth:sanctum):
 *   Route::get('guarantee-desk', [GuaranteeDeskController::class, 'index']);
 *   Route::get('guarantee-desk/{id}', [GuaranteeDeskController::class, 'show']);
 *   Route::post('guarantee-desk/{id}/confirm', [GuaranteeDeskController::class, 'confirm']);
 */
class GuaranteeDeskController extends Controller
{
    public function __construct(private GuaranteeGateService $gate)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorizeDesk();

        $contracts = Contract::with('party')
            ->where('status', 'pending_guarantee')
            ->where('requires_guarantee', true)
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $contracts->getCollection()->map(fn ($c) => $this->summary($c))->values(),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $this->authorizeDesk();

        $contract = Contract::with(['party', 'guarantees'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->summary($contract) + ['guarantees' => $contract->guarantees],
        ]);
    }

    public function confirm(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(['comments' => 'nullable|string|max:1000']);
        $contract = Contract::findOrFail($id);

        try {
            $this->gate->confirm($contract, auth()->user(), $validated['comments'] ?? null);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'دریافت ضمانت تأیید و قرارداد برای امضای واحد متقاضی ارسال شد.',
        ]);
    }

    private function authorizeDesk(): void
    {
        abort_unless(
            auth()->user()?->can(GuaranteeGateService::PERMISSION),
            403,
            'شما دسترسی لازم را ندارید.'
        );
    }

    /** فقط فیلدهای لازم برای دریافت ضمانت */
    private function summary(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'subject' => $contract->subject,
            'party' => $contract->party?->only(['id', 'name']),
            'current_amount' => $contract->current_amount,   // اگر لازم نیست حذف کنید
            'currency' => $contract->currency,
            'status' => $contract->status,
            'status_label' => $contract->status_label,
        ];
    }
}
