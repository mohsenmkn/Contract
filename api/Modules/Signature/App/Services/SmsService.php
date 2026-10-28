<?php

namespace Modules\Signature\App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = 'f2f33323246c32d48f10b122726c4812';
        $this->baseUrl = config('services.msgway.base_url', 'https://api.msgway.com/send');
    }

    /**
     * ارسال پیامک با قالب
     *
     * @param string $mobile شماره موبایل گیرنده
     * @param array $parameters پارامترهای قالب (مثل code, expiry)
     * @param int $templateId شناسه قالب پیامک
     * @return array ['success' => bool, 'message' => string, 'reference_id' => ?string]
     */
    public function sendTemplateSms(string $mobile, array $parameters, int $templateId): array
    {
        if (empty($this->apiKey)) {
            Log::warning('SMS API key not configured');
            return [
                'success' => false,
                'message' => 'سرویس پیامک پیکربندی نشده است.',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}", [
                    'mobile' => $mobile,
                    'templateId' => 23490, // بعدا تمپلیت بساز و تغییرش بده
                    'inputs' => $parameters,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'پیامک با موفقیت ارسال شد.',
                    'reference_id' => $data['referenceId'] ?? null,
                ];
            }

            Log::error('SMS send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . ($response->json('message') ?? 'نامشخص'),
            ];

        } catch (\Exception $e) {
            Log::error('SMS exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'خطا در ارتباط با سرویس پیامک.',
            ];
        }
    }

    /**
     * ارسال کد OTP به پیمانکار
     */
    public function sendOtpCode(string $mobile, string $code, int $expiryMinutes = 5, ?int $templateId = null): array
    {
        $templateId = $templateId ?? (int) config('services.msgway.template_id_default', 0);

        return $this->sendTemplateSms($mobile, [
            'code' => $code,
            'expiry' => (string) $expiryMinutes,
        ], $templateId);
    }
}
