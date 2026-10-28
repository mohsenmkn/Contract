<?php

namespace Modules\Contract\App\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Auth\App\Models\User;
use Modules\Contract\App\Models\Contract;

/**
 * مرحله‌ی دریافت ضمانت (بین امضای پیمانکار و امضای واحد متقاضی).
 * کارشناس مالی با نقش guarantee_officer ضمانت را از طریق ماژول Guarantee ثبت می‌کند
 * (قبل از ثبت، assertCanManage را صدا بزنید) و سپس confirm دریافت را تأیید می‌کند.
 */
class GuaranteeGateService
{
    public const PERMISSION = 'guarantees.manage';

    public function assertCanManage(Contract $contract, User $user): void
    {
        if (!$user->can(self::PERMISSION)) {
            throw new DomainException('دسترسی لازم برای ثبت ضمانت را ندارید.');
        }

        if (!$contract->requires_guarantee) {
            throw new DomainException('این قرارداد نیاز به ضمانت ندارد.');
        }

        if ($contract->status !== 'pending_guarantee') {
            throw new DomainException('قرارداد در مرحله‌ی دریافت ضمانت نیست.');
        }
    }

    public function confirm(Contract $contract, User $user, ?string $comments = null): Contract
    {
        return DB::transaction(function () use ($contract, $user, $comments) {
            $locked = Contract::whereKey($contract->id)->lockForUpdate()->firstOrFail();

            $this->assertCanManage($locked, $user);

            // اگر مدل Guarantee وضعیت دارد (مثلاً received)، این شرط را دقیق‌تر کنید
            if (!$locked->guarantees()->exists()) {
                throw new DomainException('ابتدا حداقل یک ضمانت برای قرارداد ثبت کنید.');
            }

            return $locked->changeStatus('pending_unit_sign', $user, $comments ?: 'تأیید دریافت ضمانت');
        });
    }
}
