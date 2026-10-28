<?php

namespace Modules\Amendment\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Amendment\App\Http\Requests\StoreAmendmentRequest;
use Modules\Amendment\App\Http\Resources\AmendmentResource;
use Modules\Amendment\App\DTOs\CreateAmendmentDTO;
use Modules\Amendment\App\Services\CreateAmendmentService;
use Modules\Amendment\App\Services\ApproveAmendmentService;
use Modules\Amendment\App\Models\Amendment;
use Modules\Contract\App\Models\Contract;

class AmendmentController extends Controller
{
    public function __construct(
        private CreateAmendmentService $createService,
        private ApproveAmendmentService $approveService,
    ) {}

    public function index(Contract $contract): JsonResponse
    {
        $amendments = $contract->amendments()
            ->with(['creator', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => AmendmentResource::collection($amendments),
        ]);
    }

    public function store(StoreAmendmentRequest $request): JsonResponse
    {
        $contract = Contract::findOrFail($request->contract_id);
        $dto = CreateAmendmentDTO::fromArray($request->validated(), $contract);
        $amendment = $this->createService->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'الحاقیه با موفقیت ایجاد شد.',
            'data' => new AmendmentResource($amendment),
        ], 201);
    }

    public function show(Amendment $amendment): JsonResponse
    {
        $amendment->load(['contract', 'creator', 'approver', 'signatures']);

        return response()->json([
            'success' => true,
            'data' => new AmendmentResource($amendment),
        ]);
    }

    public function approve(Amendment $amendment): JsonResponse
    {
        $amendment = $this->approveService->execute($amendment);

        return response()->json([
            'success' => true,
            'message' => 'الحاقیه با موفقیت تایید شد.',
            'data' => new AmendmentResource($amendment),
        ]);
    }
}
