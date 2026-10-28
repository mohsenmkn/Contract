<?php

namespace Modules\Payment\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Payment\App\Http\Requests\StorePaymentRequest;
use Modules\Payment\App\Http\Resources\PaymentResource;
use Modules\Payment\App\DTOs\CreatePaymentDTO;
use Modules\Payment\App\Services\CreatePaymentService;
use Modules\Payment\App\Models\Payment;
use Modules\Invoice\App\Models\Invoice;

class PaymentController extends Controller
{
    public function __construct(
        private CreatePaymentService $createService,
    ) {}

    public function index(Invoice $invoice): JsonResponse
    {
        $payments = $invoice->payments()
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $invoice = Invoice::findOrFail($request->invoice_id);
        $dto = CreatePaymentDTO::fromArray($request->validated(), $invoice);
        $payment = $this->createService->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'پرداخت با موفقیت ثبت شد.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['invoice', 'creator']);

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }
}
