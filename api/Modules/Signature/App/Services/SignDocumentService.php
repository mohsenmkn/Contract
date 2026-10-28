<?php

namespace Modules\Signature\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Signature\App\Models\Signature;
use Modules\Contract\App\Models\Contract;
use Modules\Amendment\App\Models\Amendment;
use Modules\Signature\App\Events\DocumentSigned; // یا DocumentSigned
use Exception;

class SignDocumentService
{
    public function execute(Signature $signature, string $action, ?string $comments = null): Signature
    {
        return DB::transaction(function () use ($signature, $action, $comments) {
            if ($signature->status !== 'pending') {
                throw new Exception('این سند قبلاً تعیین تکلیف شده است.');
            }

            if ($signature->signer_id !== auth()->id()) {
                throw new Exception('شما مجاز به تعیین تکلیف این سند نیستید.');
            }

            // ✅ پشتیبانی از هر دو اکشن sign و reject
            $signature->update([
                'status' => $action === 'sign' ? 'signed' : 'rejected',
                'comments' => $comments,
                'signature_hash' => $action === 'sign'
                    ? hash('sha256', $signature->signable_type . $signature->signable_id . auth()->id() . now()->timestamp)
                    : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'signed_at' => now(),
            ]);

            // به‌روزرسانی وضعیت سند مادر
            $this->updateSignableStatus($signature, $action);

            return $signature->fresh();
        });
    }
    /**
     * به‌روزرسانی وضعیت قرارداد یا الحاقیه پس از امضا یا رد
     */
    private function updateSignableStatus(Signature $signature, string $action): void
    {
        $signable = $signature->signable;

        if ($signable instanceof Contract) {
            if ($action === 'rejected') {
                // اگر پیمانکار رد کرد، قرارداد به حالت پیش‌نویس برمی‌گردد تا مدیر آن را اصلاح و دوباره ارسال کند
                $signable->update(['status' => 'draft']);
            } elseif ($action === 'sign') {
                // بررسی اینکه آیا همه امضای مورد نیاز انجام شده است یا خیر
                $pendingCount = $signable->signatures()
                    ->where('status', 'pending')
                    ->count();

                if ($pendingCount === 0) {
                    $signable->update([
                        'status' => 'active',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                }
            }
        }
        elseif ($signable instanceof Amendment) {
            if ($action === 'rejected') {
                $signable->update(['status' => 'rejected']);
            } elseif ($action === 'sign') {
                $pendingCount = $signable->signatures()->where('status', 'pending')->count();
                if ($pendingCount === 0) {
                    $signable->update([
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                }
            }
        }
    }
}
