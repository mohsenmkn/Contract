<?php

namespace Modules\Contract\App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contract\App\Http\Requests\StoreContractRequest;
use Modules\Contract\App\Http\Requests\UpdateContractRequest;
use Modules\Contract\App\Http\Requests\ListContractsRequest;
use Modules\Contract\App\Http\Resources\ContractResource;
use Modules\Contract\App\DTOs\CreateContractDTO;
use Modules\Contract\App\DTOs\UpdateContractDTO;
use Modules\Contract\App\Services\CreateContractService;
use Modules\Contract\App\Services\UpdateContractService;
use Modules\Contract\App\Services\ListContractsService;
use Modules\Contract\App\Services\SendContractToContractorService;
use Modules\Contract\App\Models\Contract;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ContractController extends Controller
{


    public function __construct(
        private CreateContractService $createService,
        private UpdateContractService $updateService,
        private ListContractsService $listService,
        private SendContractToContractorService $sendToContractorService,
    ) {}

    public function index(ListContractsRequest $request): JsonResponse
    {
        $contracts = $this->listService->execute($request->validated());

        return response()->json([
            'success' => true,
            'data' => ContractResource::collection($contracts),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
        ]);
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        $dto = CreateContractDTO::fromArray($request->validated());
        $contract = $this->createService->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'قرارداد با موفقیت ایجاد شد.',
            'data' => new ContractResource($contract),
        ], 201);
    }

    /**
     * ✅ اصلاح شده: مدیریت خطای ModelNotFoundException
     */
    public function show($id): JsonResponse
    {
        // ✅ اعتبارسنجی id
        if (!$id || !is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه قرارداد نامعتبر است.',
            ], 400);
        }

        try {
            $contract = Contract::with([
                'party',
                'creator',
                'approver',
                'items',
                'amendments',
                'signatures',
                'alerts',
                'invoices',
                'guarantees'
            ])->findOrFail($id);

            // ✅ بررسی دسترسی پیمانکار
            $user = auth()->user();
            if ($user->isContractor() && $contract->party_id !== $user->party_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما دسترسی به این قرارداد را ندارید.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new ContractResource($contract),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'قرارداد مورد نظر یافت نشد.',
            ], 404);
        }
    }

    /**
     * ✅ اصلاح شده: مدیریت خطای ModelNotFoundException
     */
    public function update(UpdateContractRequest $request, $id): JsonResponse
    {
        if (!$id || !is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه قرارداد نامعتبر است.',
            ], 400);
        }

        try {
            $contract = Contract::findOrFail($id);

            // ✅ بررسی دسترسی
            $user = auth()->user();
            if ($user->isContractor() && $contract->party_id !== $user->party_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما دسترسی به این قرارداد را ندارید.',
                ], 403);
            }

            $dto = UpdateContractDTO::fromArray($request->validated());
            $contract = $this->updateService->execute($contract, $dto);

            return response()->json([
                'success' => true,
                'message' => 'قرارداد با موفقیت بروزرسانی شد.',
                'data' => new ContractResource($contract),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'قرارداد مورد نظر یافت نشد.',
            ], 404);
        }
    }

    /**
     * ✅ اصلاح شده: مدیریت خطای ModelNotFoundException
     */
    public function destroy($id): JsonResponse
    {
        if (!$id || !is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه قرارداد نامعتبر است.',
            ], 400);
        }

        try {
            $contract = Contract::findOrFail($id);

            // ✅ بررسی دسترسی
            $user = auth()->user();
            if ($user->isContractor() && $contract->party_id !== $user->party_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما دسترسی به این قرارداد را ندارید.',
                ], 403);
            }

            if (!in_array($contract->status, ['draft'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'فقط قراردادهای پیش‌نویس قابل حذف هستند.',
                ], 400);
            }

            $contract->delete();

            return response()->json([
                'success' => true,
                'message' => 'قرارداد با موفقیت حذف شد.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'قرارداد مورد نظر یافت نشد.',
            ], 404);
        }
    }

    public function submit($id): JsonResponse
    {
        if (!$id || !is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه قرارداد نامعتبر است.',
            ], 400);
        }

        try {
            $contract = Contract::findOrFail($id);

            if ($contract->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'فقط قراردادهای پیش‌نویس قابل ارسال هستند.',
                ], 400);
            }

            $contract->update(['status' => 'pending_internal_approval']);

            return response()->json([
                'success' => true,
                'message' => 'قرارداد برای تایید ارسال شد.',
                'data' => new ContractResource($contract),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'قرارداد مورد نظر یافت نشد.',
            ], 404);
        }
    }


    /**
     * ارسال قرارداد به پیمانکار برای امضا
     */
    public function sendToContractor($id): JsonResponse
    {
        if (!$id || !is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه قرارداد نامعتبر است.',
            ], 400);
        }

        try {
            $contract = Contract::findOrFail($id);

            // بررسی دسترسی
            $user = auth()->user();
            if (!$user->can('contracts.submit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما دسترسی لازم برای این عملیات را ندارید.',
                ], 403);
            }

            // ارسال به پیمانکار
            $contract = $this->sendToContractorService->execute($contract);

            return response()->json([
                'success' => true,
                'message' => 'قرارداد با موفقیت برای امضا به پیمانکار ارسال شد.',
                'data' => new ContractResource($contract),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'قرارداد مورد نظر یافت نشد.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


}
