<?php

namespace Modules\Signature\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\App\Models\User;
use Modules\Contract\App\Models\Contract;


class ExecutiveSignature extends Model
{
    protected $fillable = [
        'contract_id',
        'signer_id',
        'signer_role',
        'signed_at',
        'signature_hash',
        'ip_address',
        'user_agent',
        'comments',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    /**
     * قرارداد
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * امضاکننده
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    /**
     * برچسب نقش امضاکننده
     */
    public function getSignerRoleLabel(): string
    {
        return match ($this->signer_role) {
            'ceo' => 'مدیرعامل',
            'deputy_ceo' => 'معاون مالی',
            'board_member' => 'عضو هیئت مدیره',
            default => $this->signer_role,
        };
    }

    /**
     * آیا این امضا معتبر است؟
     */
    public function isValid(): bool
    {
        return !is_null($this->signed_at) && !is_null($this->signature_hash);
    }
}
