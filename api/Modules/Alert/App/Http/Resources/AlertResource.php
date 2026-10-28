<?php

namespace Modules\Alert\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'alert_type' => $this->alert_type,
            'alert_type_label' => $this->getAlertTypeLabel(),
            'alert_type_icon' => $this->getAlertTypeIcon(),
            'alert_type_color' => $this->getAlertTypeColor(),
            'alert_date' => $this->alert_date?->format('Y-m-d'),
            'days_before' => $this->days_before,
            'message' => $this->message,
            'is_active' => (bool) $this->is_active,
            'notified_at' => $this->notified_at?->format('Y-m-d H:i:s'),
            'is_notified' => !is_null($this->notified_at),
            'days_remaining' => $this->getDaysRemaining(),
            'time_status' => $this->getTimeStatus(),
            'contract' => new \Modules\Contract\App\Http\Resources\ContractResource(
                $this->whenLoaded('contract')
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * برچسب فارسی نوع هشدار
     */
    private function getAlertTypeLabel(): string
    {
        return match ($this->alert_type) {
            'expiration' => 'پایان قرارداد',
            'guarantee'  => 'انقضای ضمانت‌نامه',
            'payment'    => 'سررسید پرداخت',
            'custom'     => 'هشدار سفارشی',
            default      => $this->alert_type ?? 'نامشخص',
        };
    }

    /**
     * آیکون نوع هشدار
     */
    private function getAlertTypeIcon(): string
    {
        return match ($this->alert_type) {
            'expiration' => 'pi pi-calendar-times',
            'guarantee'  => 'pi pi-shield',
            'payment'    => 'pi pi-money-bill',
            'custom'     => 'pi pi-bell',
            default      => 'pi pi-info-circle',
        };
    }

    /**
     * رنگ (severity) نوع هشدار برای Tag
     */
    private function getAlertTypeColor(): string
    {
        return match ($this->alert_type) {
            'expiration' => 'danger',
            'guarantee'  => 'warning',
            'payment'    => 'info',
            'custom'     => 'secondary',
            default      => 'secondary',
        };
    }

    /**
     * تعداد روزهای باقی‌مانده تا تاریخ هشدار
     */
    private function getDaysRemaining(): ?int
    {
        if (!$this->alert_date) {
            return null;
        }

        return now()->diffInDays($this->alert_date, false);
    }

    /**
     * وضعیت زمانی هشدار (برای نمایش رنگی)
     */
    private function getTimeStatus(): array
    {
        $days = $this->getDaysRemaining();

        if ($days === null) {
            return [
                'status' => 'unknown',
                'label'  => 'نامشخص',
                'color'  => 'secondary',
                'icon'   => 'pi pi-question-circle',
            ];
        }

        if ($days < 0) {
            return [
                'status' => 'expired',
                'label'  => 'گذشته',
                'color'  => 'danger',
                'icon'   => 'pi pi-times-circle',
            ];
        }

        if ($days === 0) {
            return [
                'status' => 'today',
                'label'  => 'امروز',
                'color'  => 'warning',
                'icon'   => 'pi pi-exclamation-triangle',
            ];
        }

        if ($days <= 7) {
            return [
                'status' => 'urgent',
                'label'  => 'فوری',
                'color'  => 'danger',
                'icon'   => 'pi pi-bolt',
            ];
        }

        if ($days <= 30) {
            return [
                'status' => 'warning',
                'label'  => 'هشدار',
                'color'  => 'warning',
                'icon'   => 'pi pi-exclamation-circle',
            ];
        }

        return [
            'status' => 'normal',
            'label'  => 'عادی',
            'color'  => 'success',
            'icon'   => 'pi pi-check-circle',
        ];
    }
}
