<?php

namespace Modules\Signature\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Signature\App\Http\Requests\SignDocumentRequest;
use Modules\Signature\App\Http\Resources\SignatureResource;
use Modules\Signature\App\Services\SignDocumentService;
use Modules\Signature\App\Services\SigningService;
use Modules\Signature\App\Services\OtpService;
use Modules\Signature\App\Services\SmsService;
use Modules\Signature\App\Services\SignatureImageService;
use Modules\Signature\App\Models\Signature;
use Modules\Signature\App\Models\OtpCode;
use Modules\Contract\App\Models\Contract;
use Modules\Amendment\App\Models\Amendment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class SignatureController extends Controller
{
    public function __construct(
        private SignDocumentService $signService,
        private SigningService $signingService,
        private OtpService $otpService,
        public SmsService $smsService,
    ) {}

    /**
     * لیست امضاهای در انتظار من
     */
    public function myPendingSignatures(): JsonResponse
    {
        $user = auth()->user();
        $signatures = Signature::awaitingUser($user)
            ->with(['signable', 'signer'])
            ->orderBy('created_at', 'desc')
            ->get()
            // Keep the pending list consistent with the detail/OTP authorization checks.
            // Role-only matches can otherwise expose stale, wrong-phase, or already-signed slots.
            ->filter(fn (Signature $signature) => $this->canAccessSignature($signature, $user))
            ->values();

        return response()->json([
            'success' => true,
            'data' => SignatureResource::collection($signatures),
        ]);
    }

    /**
     * ✅ دریافت جزئیات سند برای امضا (شامل متن قرارداد)
     */
    public function show(Signature $signature): JsonResponse
    {
        // بررسی مالکیت
        if (!$this->canAccessSignature($signature)) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این سند را ندارید.',
            ], 403);
        }

        $signable = $signature->signable;
        $signable->load(['party', 'creator', 'items']);

        $data = [
            'signature' => new SignatureResource($signature),
            'document' => [
                'type' => $signable instanceof Contract ? 'contract' : 'amendment',
                'number' => $signable->contract_number ?? $signable->amendment_number,
                'subject' => $signable->subject ?? $signable->description,
                'content' => $signable->content ?? null,  // ✅ متن قرارداد
                'pages_count' => $signable->pages_count ?? null,
                'amount' => $signable->current_amount ?? $signable->new_amount,
                'party' => $signable->party?->name,
                'start_date' => $signable->start_date?->format('Y-m-d'),
                'end_date' => $signable->end_date ?? $signable->new_end_date,
            ],
            'has_pending_otp' => $this->otpService->getLatestPending($signable, auth()->user()) !== null,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * ✅ درخواست ارسال کد OTP
     */
    public function requestOtp(Request $request, Signature $signature): JsonResponse
    {
        if (!$this->canAccessSignature($signature)
            || ($signature->group === null && (int) $signature->signer_id !== (int) auth()->id())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز.',
            ], 403);
        }

        if ($signature->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'این سند قبلاً تعیین تکلیف شده است.',
            ], 400);
        }

        try {
            try {
                app(SignatureImageService::class)->activeFor(auth()->user());
            } catch (\DomainException) {
                return response()->json([
                    'success' => false,
                    'message' => 'برای تعریف امضای الکترونیکی به واحد حقوقی و قراردادها مراجعه نمایید.',
                ], 422);
            }

            $signable = $signature->signable;
            $otp = $this->otpService->generateAndSend($signable, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'کد تایید به شماره موبایل شما ارسال شد.',
                'data' => [
                    'otp_id' => $otp->id,
                    'expires_at' => $otp->expires_at->format('Y-m-d H:i:s'),
                    'expires_in_seconds' => now()->diffInSeconds($otp->expires_at),
                    'mobile' => $this->maskMobile(auth()->user()->mobile),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ✅ تایید کد OTP و امضای سند
     */
    public function verifyAndSign(Request $request, Signature $signature): JsonResponse
    {
        $request->validate([
            'otp_id' => 'required|exists:otp_codes,id',
            'code' => 'required|string|size:6',
            'comments' => 'nullable|string|max:1000',
        ]);

        if (!$this->canAccessSignature($signature)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز.',
            ], 403);
        }

        if ($signature->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'این سند قبلاً تعیین تکلیف شده است.',
            ], 400);
        }

        $otp = OtpCode::findOrFail($request->otp_id);

        // بررسی تعلق OTP به همین سند و کاربر
        if ($otp->otpable_type !== get_class($signature->signable) ||
            $otp->otpable_id !== $signature->signable_id ||
            $otp->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید نامعتبر است.',
            ], 400);
        }

        // اعتبارسنجی کد
        if (!$this->otpService->verify($otp, $request->code)) {
            $remainingAttempts = $otp->max_attempts - $otp->attempts;

            if ($otp->isAttemptsExhausted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعداد تلاش‌های ناموفق به پایان رسید. لطفاً کد جدیدی درخواست کنید.',
                    'data' => ['attempts_exhausted' => true],
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => "کد وارد شده صحیح نیست. {$remainingAttempts} تلاش دیگر باقی مانده است.",
                'data' => ['remaining_attempts' => $remainingAttempts],
            ], 400);
        }

        // امضای سند
        if ($signature->group !== null && $signature->signable instanceof Contract) {
            $this->signingService->complete($signature, auth()->user(), $request->comments);
            $signature->refresh();
        } else {
            // Legacy final-approval slots did not have a signing group. Claim an
            // unassigned eligible slot only after its OTP has been verified.
            if ($this->isLegacyExecutiveSlotFor($signature, auth()->user())) {
                $signature->signer_id = auth()->id();
                $signature->role = collect(auth()->user()->getRoleNames())
                    ->first(fn (string $role) => in_array($role, $signature->allowed_roles ?? [], true))
                    ?? collect(auth()->user()->getRoleNames())->first(fn (string $role) => in_array($role, ['deputy_ceo', 'ceo', 'board_member'], true));
                $signature->save();
            }

            $signature = $this->signService->execute(
                $signature,
                'sign',
                $request->comments ?? "امضا با کد تایید OTP"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'قرارداد با موفقیت امضا شد.',
            'data' => new SignatureResource($signature),
        ]);
    }

    /**
     * پوشاندن بخشی از شماره موبایل
     */
    private function maskMobile(string $mobile): string
    {
        if (strlen($mobile) < 7) return $mobile;
        return substr($mobile, 0, 4) . '***' . substr($mobile, -3);
    }

    private function canAccessSignature(Signature $signature, $user = null): bool
    {
        $user ??= auth()->user();
        if ($signature->group !== null) {
            if ($signature->group === 'executive') {
                return $this->canAccessExecutiveSlot($signature, $user);
            }
            return $this->signingService->canSignSlot($signature, $user);
        }

        return (int) $signature->signer_id === (int) $user->id
            || $this->isLegacyExecutiveSlotFor($signature, $user);
    }

    private function canAccessExecutiveSlot(Signature $signature, $user): bool
    {
        if ($signature->status !== 'pending' || $signature->signable_type !== Contract::class) {
            return false;
        }

        $contract = $signature->signable;
        return $contract instanceof Contract
            && $contract->status === 'pending_executive_sign'
            && (int) $signature->content_version === (int) $contract->content_version
            && $user->canSignThisContract($contract);
    }

    private function isLegacyExecutiveSlotFor(Signature $signature, $user): bool
    {
        if ($signature->group !== null
            || $signature->status !== 'pending'
            || $signature->signer_id !== null
            || $signature->signable_type !== Contract::class
        ) {
            return false;
        }

        $contract = $signature->signable;
        if (!$contract instanceof Contract || $contract->status !== 'pending_executive_sign'
            || !$user->canSignThisContract($contract)
        ) {
            return false;
        }

        $allowedRoles = $signature->allowed_roles ?? [];
        return empty($allowedRoles)
            || $user->hasAnyRole($allowedRoles);
    }

/**
* رد سند (بدون نیاز به OTP)
*/
    public function rejectDocument(Request $request, Signature $signature): JsonResponse
    {
        $request->validate([
            'comments' => 'required|string|min:3|max:1000',
        ]);

        if (!$this->canAccessSignature($signature)
            || ($signature->group === null && (int) $signature->signer_id !== (int) auth()->id())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز.',
            ], 403);
        }

        if ($signature->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'این سند قبلاً تعیین تکلیف شده است.',
            ], 400);
        }

        try {
            if ($signature->group !== null && $signature->signable instanceof Contract) {
                $this->signingService->reject($signature, auth()->user(), $request->comments);
                $signature->refresh();
            } else {
                $signature = $this->signService->execute(
                    $signature,
                    'reject',
                    $request->comments
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'سند با موفقیت رد شد.',
                'data' => new SignatureResource($signature),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
