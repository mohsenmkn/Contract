<?php

namespace Modules\Alert\App\Services;

use Illuminate\Support\Collection;
use Modules\Alert\App\Models\ContractAlert;
use Modules\Contract\App\Notifications\ContractExpiringNotification;
use Modules\Contract\App\Services\ContractSettings;
use Modules\Signature\App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckExpiringContractsService
{
    public function __construct(private ContractSettings $settings, private SmsService $sms) {}

    public function execute(): Collection
    {
        $settings = $this->settings->expiryAlertSettings();
        $channels = $settings['channels'];
        $alerts = ContractAlert::where('alert_type', 'expiration')->whereNull('notified_at')
            ->whereDate('alert_date', '<=', today())->active()
            ->whereHas('contract', fn ($query) => $query->whereIn('status', ['active', 'expiring']))
            ->with(['contract.party', 'contract.creator'])
            ->get();

        $notified = collect();

        foreach ($alerts as $alert) {
            if (!$alert->contract) {
                continue;
            }

            $recipient = $alert->contract->creator;
            if (in_array('in_app', $channels, true) && $recipient) {
                $recipient->notify(new ContractExpiringNotification($alert, ['database']));
            }
            if (in_array('email', $channels, true)) {
                $emails = $settings['email_addresses'] ?: array_filter([$recipient?->email]);
                foreach (array_unique($emails) as $email) {
                    Notification::route('mail', $email)->notify(new ContractExpiringNotification($alert, ['mail']));
                }
            }
            if (in_array('sms', $channels, true)) {
                $message = $this->smsMessage($alert);
                $phones = $settings['phone_numbers'] ?: array_filter([$recipient?->mobile]);
                foreach (array_unique($phones) as $phone) {
                    $result = $this->sms->sendTemplateSms(
                        $phone,
                        [$message],
                        (int) config('services.msgway.template_id_default', 23490)
                    );
                    if (empty($result['success'])) {
                        Log::warning('Contract expiration SMS could not be sent.', [
                            'alert_id' => $alert->id,
                            'contract_id' => $alert->contract_id,
                            'reason' => $result['message'] ?? null,
                        ]);
                    }
                }
            }
            $alert->update(['notified_at' => now()]);
            $notified->push($alert);
        }

        return $notified;
    }

    private function smsMessage(ContractAlert $alert): string
    {
        $contract = $alert->contract;
        $days = (int) $alert->days_before;
        $endDate = $contract->end_date?->format('Y/m/d');
        return "هشدار پایان قرارداد: قرارداد شماره {$contract->contract_number} با موضوع {$contract->subject}، "
            . "{$days} روز دیگر در تاریخ {$endDate} به پایان می‌رسد. لطفاً برای تمدید یا خاتمه آن اقدام کنید.";
    }
}
