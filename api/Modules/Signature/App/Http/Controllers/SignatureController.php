<?php

namespace Modules\Signature\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Signature\App\Http\Requests\SignDocumentRequest;
use Modules\Signature\App\Http\Resources\SignatureResource;
use Modules\Signature\App\Services\SignDocumentService;
use Modules\Signature\App\Services\OtpService;
use Modules\Signature\App\Services\SmsService;
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
        private OtpService $otpService,
        public SmsService $smsService,
    ) {}

    /**
     * لیست امضاهای در انتظار من
     */
    public function myPendingSignatures(): JsonResponse
    {
        $signatures = Signature::where('signer_id', auth()->id())
            ->where('status', 'pending')
            ->with(['signable', 'signer'])
            ->orderBy('created_at', 'desc')
            ->get();

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
        if ($signature->signer_id !== auth()->id()) {
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
        if ($signature->signer_id !== auth()->id()) {
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
            $signable = $signature->signable;
            $templateId = (int) $request->input('template_id', 0);

            $otp = $this->otpService->generateAndSend($signable, auth()->user(), $templateId);

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

        if ($signature->signer_id !== auth()->id()) {
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
        $signature = $this->signService->execute(
            $signature,
            'sign',
            $request->comments ?? "امضا با کد تایید OTP (کد: {$request->code})"
        );

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

/**
* رد سند (بدون نیاز به OTP)
*/
    public function rejectDocument(Request $request, Signature $signature): JsonResponse
    {
        $request->validate([
            'comments' => 'required|string|min:3|max:1000',
        ]);

        if ($signature->signer_id !== auth()->id()) {
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
            // استفاده از SignDocumentService برای رد سند
            $signature = $this->signService->execute(
                $signature,
                'reject',
                $request->comments
            );

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
