<?php

namespace Modules\Payment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Payment\App\Models\Payment;
use Modules\Payment\App\DTOs\CreatePaymentDTO;
use Modules\Payment\App\Services\PaymentNumberGenerator;
use Modules\Payment\App\Events\PaymentCreated;

class CreatePaymentService
{
    public function __construct(
        private PaymentNumberGenerator $numberGenerator
    ) {}

    public function execute(CreatePaymentDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto) {
            $invoice = $dto->invoice;

            if ($invoice->status !== 'approved') {
                throw new \Exception('فقط صورت‌وضعیت‌های تایید شده قابل پرداخت هستند.');
            }

            if ($dto->amount > $invoice->remaining_amount) {
                throw new \Exception('مبلغ پرداخت بیشتر از مانده صورت‌وضعیت است.');
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_number' => $this->numberGenerator->generate(),
                'amount' => $dto->amount,
                'currency' => $dto->currency ?? 'IRR',
                'payment_date' => $dto->paymentDate,
                'method' => $dto->method,
                'reference_number' => $dto->referenceNumber,
                'check_number' => $dto->checkNumber,
                'check_due_date' => $dto->checkDueDate,
                'status' => $dto->status ?? 'pending',
                'description' => $dto->description,
                'created_by' => auth()->id(),
            ]);

            if ($payment->status === 'completed') {
                $this->checkInvoicePaidStatus($invoice);
            }

            event(new PaymentCreated($payment));

            return $payment;
        });
    }

    private function checkInvoicePaidStatus($invoice): void
    {
        if ($invoice->isFullyPaid()) {
            $invoice->update(['status' => 'paid']);
        }
    }
}
