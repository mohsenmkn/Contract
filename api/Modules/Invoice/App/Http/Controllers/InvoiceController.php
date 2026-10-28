<?php

namespace Modules\Invoice\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Invoice\App\Http\Requests\StoreInvoiceRequest;
use Modules\Invoice\App\Http\Resources\InvoiceResource;
use Modules\Invoice\App\DTOs\CreateInvoiceDTO;
use Modules\Invoice\App\Services\CreateInvoiceService;
use Modules\Invoice\App\Services\SubmitInvoiceService;
use Modules\Invoice\App\Services\ApproveInvoiceService;
use Modules\Invoice\App\Models\Invoice;
use Modules\Contract\App\Models\Contract;

class InvoiceController extends Controller
{
    public function __construct(
        private CreateInvoiceService $createService,
        private SubmitInvoiceService $submitService,
        private ApproveInvoiceService $approveService,
    ) {}

    public function index(Contract $contract): JsonResponse
    {
        $invoices = $contract->invoices()
            ->with(['submitter', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => InvoiceResource::collection($invoices),
        ]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $dto = CreateInvoiceDTO::fromArray($request->validated());
        $invoice = $this->createService->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'صورت‌وضعیت با موفقیت ایجاد شد.',
            'data' => new InvoiceResource($invoice),
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['contract', 'items', 'payments', 'submitter', 'approver']);

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function submit(Invoice $invoice): JsonResponse
    {
        $invoice = $this->submitService->execute($invoice);

        return response()->json([
            'success' => true,
            'message' => 'صورت‌وضعیت ارسال شد.',
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function approve(Invoice $invoice): JsonResponse
    {
        $invoice = $this->approveService->execute($invoice);

        return response()->json([
            'success' => true,
            'message' => 'صورت‌وضعیت تایید شد.',
            'data' => new InvoiceResource($invoice),
        ]);
    }
}
