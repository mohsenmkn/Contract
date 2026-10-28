<?php

namespace Modules\Auth\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Contract\App\Models\Contract;
use Spatie\Permission\Traits\HasRoles;
use Modules\Auth\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'password',
        'national_code',
        'personnel_code',
        'otp_code',
        'otp_expires_at',
        'settings',
        'avatar',
        'is_active',
        'employee_type',
        'party_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * رابطه با Party (شرکت پیمانکاری)
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(\Modules\Party\App\Models\Party::class);
    }

    /**
     * آیا کاربر پیمانکار است؟
     */
    public function isContractor(): bool
    {
        return $this->employee_type === 'contractor';
    }

    /**
     * آیا کاربر پرسنل است؟
     */
    public function isPersonnel(): bool
    {
        return $this->employee_type === 'personnel' || $this->employee_type === null;
    }

    /**
     * آیا کاربر پیمانکار متصل به شرکت است؟
     */
    public function isLinkedContractor(): bool
    {
        return $this->isContractor() && !is_null($this->party_id);
    }

    /**
     * آیا کاربر اجازه ورود به سیستم را دارد؟
     */
    public function canLogin(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // پیمانکاران باید به یک Party متصل باشند
        if ($this->isContractor()) {
            return !is_null($this->party_id);
        }

        return true;
    }

    /**
     * ارسال نوتیفیکیشن ریست پسورد
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
    /**
     * دریافت کوئری قراردادهای قابل دسترسی برای کاربر
     * پیمانکاران فقط قراردادهای شرکت خود را می‌بینند
     */
    public function getAccessibleContracts()
    {
        if ($this->isContractor() && $this->party_id) {
            // پیمانکار فقط قراردادهایی را می‌بیند که party_id آن‌ها برابر با party_id خودش باشد
            return \Modules\Contract\App\Models\Contract::where('party_id', $this->party_id);
        }

        // سایر کاربران (مدیران، پرسنل) همه قراردادها را می‌بینند
        return \Modules\Contract\App\Models\Contract::query();
    }

    /**
     * بررسی دسترسی به یک قرارداد خاص
     */
    public function canAccessContract($contract): bool
    {
        if ($this->isContractor()) {
            return $contract->party_id === $this->party_id;
        }

        return true;
    }

    /**
     * واحد سازمانی که کاربر نماینده آن است
     */
    public function organizationalUnit(): HasOne
    {
        return $this->hasOne(\Modules\Organization\App\Models\UnitRepresentative::class)
            ->where('is_active', true)
            ->with('unit');
    }

    /**
     * آیا کاربر نماینده یک واحد است؟
     */
    public function isUnitRepresentative(): bool
    {
        return $this->organizationalUnit()->exists();
    }

    /**
     * دریافت واحد سازمانی کاربر
     */
    public function getUnit(): ?\Modules\Organization\App\Models\OrganizationalUnit
    {
        $rep = $this->organizationalUnit()->first();
        return $rep?->unit;
    }

    /**
     * آیا کاربر می‌تواند قرارداد امضا کند؟ (مدیرعامل، معاون، هیئت مدیره)
     */
    public function canSignExecutive(): bool
    {
        return $this->hasAnyRole(['ceo', 'deputy_ceo', 'board_member']);
    }

    /**
     * آیا کاربر می‌تواند این قرارداد را امضا کند؟ (بر اساس مبلغ)
     */
    public function canSignThisContract(Contract $contract): bool
    {
        if (!$this->canSignExecutive()) {
            return false;
        }

        // اگر قرارداد بالای 900 میلیون است، فقط مدیرعامل یا هیئت مدیره
        if ($contract->requiresHighLevelSignature()) {
            return $this->hasAnyRole(['ceo', 'board_member']);
        }

        // اگر زیر 900 میلیون است، معاون مالی هم می‌تواند
        return true;
    }

}
