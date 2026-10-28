<?php

namespace Modules\Guarantee\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Guarantee\App\Http\Requests\StoreGuaranteeRequest;
use Modules\Guarantee\App\Http\Resources\GuaranteeResource;
use Modules\Guarantee\App\DTOs\CreateGuaranteeDTO;
use Modules\Guarantee\App\Services\CreateGuaranteeService;
use Modules\Guarantee\App\Models\Guarantee;
use Modules\Contract\App\Models\Contract;

class GuaranteeController extends Controller
{
    public function __construct(
        private CreateGuaranteeService $createService,
    ) {}

    public function index(Contract $contract): JsonResponse
    {
        $guarantees = $contract->guarantees()
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => GuaranteeResource::collection($guarantees),
        ]);
    }

    public function store(StoreGuaranteeRequest $request): JsonResponse
    {
        $dto = CreateGuaranteeDTO::fromArray($request->validated());
        $guarantee = $this->createService->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'ضمانت‌نامه با موفقیت ثبت شد.',
            'data' => new GuaranteeResource($guarantee),
        ], 201);
    }

    public function show(Guarantee $guarantee): JsonResponse
    {
        $guarantee->load(['contract', 'creator']);

        return response()->json([
            'success' => true,
            'data' => new GuaranteeResource($guarantee),
        ]);
    }

    public function release(Guarantee $guarantee): JsonResponse
    {
        if ($guarantee->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'فقط ضمانت‌نامه‌های فعال قابل آزادسازی هستند.',
            ], 400);
        }

        $guarantee->update(['status' => 'released']);

        return response()->json([
            'success' => true,
            'message' => 'ضمانت‌نامه آزاد شد.',
            'data' => new GuaranteeResource($guarantee),
        ]);
    }
}
