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
use Modules\Contract\App\Models\Contract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Signature\App\Models\Signature;

class ContractController extends Controller
{


    public function __construct(
        private CreateContractService $createService,
        private UpdateContractService $updateService,
        private ListContractsService $listService,
        private ContractWorkflowService $workflowService,
        private \Modules\Contract\App\Services\EmployerFrameworkService $employerFramework,
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
                'items.employerContractItem',
                'parentContract.items',
                'amendments',
                'signatures.signer',
                'executiveSignatures.signer',
                'alerts',
                'invoices',
                'guarantees'
            ])->findOrFail($id);

            $employerContract = $contract->type === 'upstream'
                ? $contract
                : $contract->parentContract;
            if ($employerContract) {
                $excludeContractId = $contract->type === 'downstream' ? $contract->id : null;
                $remaining = $this->employerFramework->remainingQuantities($employerContract, $excludeContractId);
                foreach ($employerContract->items as $item) {
                    $item->setAttribute('remaining_framework_quantity', $remaining[$item->id] ?? 0);
                }
                $employerContract->setAttribute(
                    'remaining_framework_amount',
                    $this->employerFramework->remainingAmount($employerContract, $excludeContractId),
                );
            }

            // ✅ بررسی دسترسی پیمانکار
            $user = auth()->user();
            if (!$user->can('contracts.read')) {
                return response()->json(['success' => false, 'message' => 'شما اجازه مشاهده این قرارداد را ندارید.'], 403);
            }
            if (!$user->canAccessContract($contract)) {
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
            if (!$user->canAccessContract($contract)) {
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
            if (!$user->can('contracts.delete') || !$user->canAccessContract($contract)) {
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

    public function returnToRequester(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'comments' => 'required|string|max:2000',
        ]);

        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->returnToRequester(
                $contract,
                auth()->user(),
                $validated['comments']
            );

            return response()->json([
                'success' => true,
                'message' => 'قرارداد برای اصلاح به واحد درخواست‌کننده بازگردانده شد.',
                'data' => new ContractResource($contract),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

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
    public function submitToContractReview($id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->submitToContractReview($contract, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'قرارداد برای بررسی به واحد قراردادها ارسال شد.',
                'data' => new ContractResource($contract),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

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
                $validated
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

    public function updateGuaranteeRequirement(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'requires_guarantee' => 'required|boolean',
            'guarantee_type' => 'required_if:requires_guarantee,true|nullable|in:performance,advance_payment,bid,retention',
            'guarantee_instrument_type' => 'required_if:requires_guarantee,true|nullable|in:check,promissory_note,bank_guarantee',
            'requires_board_signature' => 'required|boolean',
        ]);

        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->updateGuaranteeRequirement(
                $contract,
                auth()->user(),
                (bool) $validated['requires_guarantee'],
                $validated['guarantee_type'] ?? null,
                $validated['guarantee_instrument_type'] ?? null,
                (bool) $validated['requires_board_signature']
            );

            return response()->json([
                'success' => true,
                'message' => 'نیاز به ضمانت قرارداد ذخیره شد.',
                'data' => new ContractResource($contract),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function returnToRequester(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'comments' => 'required|string|max:2000',
        ]);

        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->returnToRequester(
                $contract,
                auth()->user(),
                $validated['comments']
            );

            return response()->json([
                'success' => true,
                'message' => 'قرارداد برای اصلاح به واحد درخواست‌کننده بازگردانده شد.',
                'data' => new ContractResource($contract),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
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
        abort_unless($user->can('contracts.read'), 403);
        $slot = Signature::awaitingUser($user)
            ->where('signable_type', Contract::class)
            ->where('signable_id', $contract->id)
            ->where('group', 'executive')
            ->first();
        $canSign = $slot !== null;

        return response()->json([
            'success' => true,
            'data' => [
                'can_sign' => $canSign,
                'signature_id' => $slot?->id,
                'requires_board_signature' => (bool) $contract->requires_board_signature,
                'reason' => $canSign ? '' : 'شما مجاز به امضای این قرارداد نیستید.',
            ],
        ]);
    }

    /**
     * ابلاغ به پیمانکار
     */
    public function notify(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'letter_body' => 'required|string|max:30000',
        ]);

        try {
            $contract = Contract::findOrFail($id);
            $contract = $this->workflowService->notifyContractor($contract, auth()->user(), $validated['letter_body']);

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
        $user = auth()->user();
        abort_unless($user && !$user->hasRole('contractor') && $user->can('contracts.read'), 403);
        $contract = Contract::findOrFail($id);
        abort_unless($user->canAccessContract($contract), 403);

        // دریافت تاریخچه از جدول audit_logs یا events
        $history = DB::table('contract_status_history')
            ->where('contract_id', $id)
            ->leftJoin('users', 'contract_status_history.user_id', '=', 'users.id')
            ->select('contract_status_history.*', 'users.name as actor_name')
            ->orderBy('contract_status_history.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->status_label = Contract::STATUSES[$item->to_status] ?? $item->to_status;
                $item->title = $item->to_status === 'rejected_by_legal'
                    ? 'رد توسط واحد حقوقی'
                    : $item->status_label;
                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

}
