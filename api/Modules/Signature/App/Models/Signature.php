<?php

namespace Modules\Signature\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Auth\App\Models\User;

class Signature extends Model
{
    protected $fillable = [
        'signable_type',
        'signable_id',
        'signer_id',
        'signer_type',
        'group',
        'role',
        'allowed_roles',
        'kind',
        'sign_order',
        'content_version',
        'status',            // pending | signed | invalidated
        'comments',
        'signature_hash',
        'image_path',
        'image_hash',
        'ip_address',
        'user_agent',
        'signed_at',
        'invalidated_at',
        'invalidated_reason',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'invalidated_at' => 'datetime',
        'allowed_roles' => 'array',
    ];

    // مسیر فایل روی دیسک نباید در پاسخ API بیرون برود
    protected $hidden = ['image_path'];

    protected $appends = ['required_roles_label'];

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    /** امضاهای معتبر (باطل‌نشده) */
    public function scopeValid($query)
    {
        return $query->whereIn('status', ['pending', 'signed']);
    }

    /**
     * اسلات‌های pending که کاربر می‌تواند امضا کند (بر اساس نقش + واحد/طرف قرارداد).
     * جایگزین فیلتر قدیمی signer_id برای endpoint «امضاهای در انتظار من».
     */
    public function scopeAwaitingUser($query, User $user)
    {
        $roles = $user->getRoleNames()->all();
        $unitRoles = array_values(array_intersect($roles, ['unit_head', 'unit_manager', 'unit_deputy']));

        return $query->where('signatures.status', 'pending')
            ->where(function ($q) use ($roles, $user) {
                $q->where(function ($legacy) use ($user) {
                    $legacy->whereNull('signatures.group')->where('signatures.signer_id', $user->id);
                });
                foreach ($roles as $role) {
                    $q->orWhereJsonContains('signatures.allowed_roles', $role);
                }
            })
            ->where(function ($accessible) use ($user, $unitRoles) {
                $accessible->where(function ($contract) use ($user, $unitRoles) {
                    $contract->where('signatures.signable_type', \Modules\Contract\App\Models\Contract::class)
                        ->whereExists(function ($sub) use ($user, $unitRoles) {
                            $sub->selectRaw('1')
                                ->from('contracts')
                                ->whereColumn('contracts.id', 'signatures.signable_id')
                                ->whereNull('contracts.deleted_at')
                                ->where(function ($scope) use ($user, $unitRoles) {
                                    $scope->whereNull('signatures.group')
                                        ->orWhere(function ($grouped) use ($user, $unitRoles) {
                                            $grouped->where(function ($unitGroup) use ($user, $unitRoles) {
                                                $unitGroup->where('signatures.group', '!=', 'unit')
                                                    ->orWhereExists(function ($unit) use ($user, $unitRoles) {
                                                        $unit->selectRaw('1')
                                                            ->from('contract_unit_signers')
                                                            ->whereColumn('contract_unit_signers.unit_id', 'contracts.unit_id')
                                                            ->where('contract_unit_signers.user_id', $user->id)
                                                            ->whereIn('contract_unit_signers.unit_role', $unitRoles)
                                                            ->whereRaw('JSON_CONTAINS(signatures.allowed_roles, JSON_QUOTE(contract_unit_signers.unit_role))');
                                                    });
                                            })->where(function ($partyGroup) use ($user) {
                                                $partyGroup->where('signatures.group', '!=', 'contractor')
                                                    ->orWhere('contracts.party_id', $user->party_id);
                                            });
                                        });
                                });
                        });
                })->orWhere(function ($legacyOther) use ($user) {
                    $legacyOther->where('signatures.signable_type', '!=', \Modules\Contract\App\Models\Contract::class)
                        ->whereNull('signatures.group')
                        ->where('signatures.signer_id', $user->id);
                });
            });
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    /** برچسب فارسی نقش‌های مجاز، مثلاً «رئیس واحد / مدیر واحد / معاون واحد» */
    public function getRequiredRolesLabelAttribute(): ?string
    {
        if (empty($this->allowed_roles)) {
            return null;
        }

        $labels = config('signing.role_labels', []);

        return collect($this->allowed_roles)
            ->map(fn ($role) => $labels[$role] ?? $role)
            ->implode(' / ');
    }
}
