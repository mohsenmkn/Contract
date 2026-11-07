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

    public function sendTemplateSms(
        string $mobile,
        array $parameters,
        int $templateId
    ): array {
        $apiKey = (string) config('services.msgway.api_key');

        if ($apiKey === '') {
            Log::error('Msgway API key is not configured.');

            return [
                'success' => false,
                'message' => 'سرویس پیامک پیکربندی نشده است.',
            ];
        }

        $params = [
            'mobile' => $mobile,
            'method' => 'sms',
            'provider' => (int) config('services.msgway.provider', 0),
            'templateID' => $templateId,
            'params' => array_values($parameters),
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'apiKey' => $apiKey,
                ])
                ->post('https://api.msgway.com/send', $params);

        } catch (\Throwable $e) {
            Log::error("SMS request failed for {$mobile}: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => 'خطا در ارتباط با سرویس پیامک.',
            ];
        }

        Log::info("SMS Response for {$mobile} (HTTP {$response->status()}): " . $response->body());

        if ($response->successful()) {
            $data = $response->json() ?? [];

            return [
                'success' => true,
                'message' => 'درخواست ارسال پیامک با موفقیت ثبت شد.',
                'reference_id' => $data['referenceId']
                    ?? $data['reference_id']
                    ?? null,
            ];
        }

        Log::error('Msgway SMS send failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => false,
            'message' => 'خطا در ارسال پیامک: '
                . ($response->json('message') ?? $response->body()),
        ];
    }


    /**
     * ارسال کد OTP به پیمانکار
     */
    public function sendOtpCode(string $mobile, string $fullName, string $code, ?int $templateId = null): array
    {
        $templateId = $templateId ?? (int) config('services.msgway.template_id_signature', 25370);

        return $this->sendTemplateSms($mobile, [
            $fullName,
            $code,
        ], $templateId);
    }
}
