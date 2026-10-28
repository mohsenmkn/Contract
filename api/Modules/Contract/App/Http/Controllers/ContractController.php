<?php

namespace Modules\Contract\App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Http\Requests\StoreContractRequest;
use Modules\Contract\App\Http\Requests\UpdateContractRequest;
use Modules\Contract\App\Http\Requests\ListContractsRequest;
use Modules\Contract\App\Http\Resources\ContractResource;
use Modules\Contract\App\DTOs\CreateContractDTO;
use Modules\Contract\App\DTOs\UpdateContractDTO;
use Modules\Contract\App\Services\ContractWorkflowService;
use Modules\Contract\App\Services\CreateContractService;
use Modules\Contract\App\Services\UpdateContractService;
use Modules\Contract\App\Services\ListContractsService;
use Modules\Contract\App\Services\SendContractToContractorService;
use Modules\Contract\App\Models\Contract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Signature\App\Services\ExecutiveSignService;

class ContractController extends Controller
{


    public function __construct(
        private CreateContractService $createService,
        private UpdateContractService $updateService,
        private ListContractsService $listService,
        private SendContractToContractorService $sendToContractorService,
        private ContractWorkflowService $workflowService,
        private ExecutiveSignService $executiveSignService,
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

//    public function submit($id): JsonResponse
//    {
//        if (!$id || !is_numeric($id)) {
//            return response()->json([
//                'success' => false,
//                'message' => 'شناسه قرارداد نامعتبر است.',
//            ], 400);
//        }
//
//        try {
//            $contract = Contract::findOrFail($id);
//
//            if ($contract->status !== 'draft') {
//                return response()->json([
//                    'success' => false,
//                    'message' => 'فقط قراردادهای پیش‌نویس قابل ارسال هستند.',
//                ], 400);
//            }
//
//            $contract->update(['status' => 'pending_internal_approval']);
//
//            return response()->json([
//                'success' => true,
//                'message' => 'قرارداد برای تایید ارسال شد.',
//                'data' => new ContractResource($contract),
//            ]);
//        } catch (ModelNotFoundException $e) {
//            return response()->json([
//                'success' => false,
//                'message' => 'قرارداد مورد نظر یافت نشد.',
//            ], 404);
//        }
//    }


    /**
     * ارسال قرارداد به پیمانکار برای امضا

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
*/

    /**
     * ارسال قرارداد به حقوقی
     */
    public function submitToLegal($id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->submitToLegal($contract, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'قرارداد با موفقیت به واحد حقوقی ارسال شد.',
                'data' => $contract,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * تنظیم پیش‌نویس حقوقی
     */
    public function legalDraft(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'comments' => 'nullable|string',
        ]);

        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->createLegalDraft(
                $contract,
                auth()->user(),
                $validated['content']
            );

            return response()->json([
                'success' => true,
                'message' => 'پیش‌نویس حقوقی با موفقیت ذخیره شد.',
                'data' => $contract,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ارسال به پیمانکار
     */
    public function sendToContractor($id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->sendToContractor($contract, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'قرارداد با موفقیت به پیمانکار ارسال شد.',
                'data' => $contract,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ثبت امضای پیمانکار

    public function markContractorSigned($id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->markContractorSigned($contract);

            return response()->json([
                'success' => true,
                'message' => 'امضای پیمانکار ثبت شد.',
                'data' => $contract,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    } */

    /**
     * امضای اجرایی

    public function executiveSign(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:ceo,deputy_ceo,board_member',
            'comments' => 'nullable|string',
        ]);

        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->executiveSignService->sign(
                $contract,
                auth()->user(),
                $validated['role'],
                $validated['comments']
            );

            return response()->json([
                'success' => true,
                'message' => 'امضای اجرایی با موفقیت ثبت شد.',
                'data' => $contract,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }*/

    /**
     * بررسی توانایی امضا
     */
    public function canSign($id): JsonResponse
    {
        $contract = Contract::findOrFail($id);
        $user = auth()->user();

        $canSign = $this->executiveSignService->canSign($contract, $user);

        return response()->json([
            'success' => true,
            'data' => [
                'can_sign' => $canSign,
                'reason' => $canSign ? '' : 'شما مجاز به امضای این قرارداد نیستید.',
            ],
        ]);
    }

    /**
     * ابلاغ به پیمانکار
     */
    public function notify($id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->notifyContractor($contract, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'قرارداد با موفقیت ابلاغ شد.',
                'data' => $contract,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * تاریخچه Workflow
     */

    public function workflowHistory($id): JsonResponse
    {
        $contract = Contract::findOrFail($id);

        // دریافت تاریخچه از جدول audit_logs یا events
        $history = DB::table('contract_status_history')
            ->where('contract_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

}
