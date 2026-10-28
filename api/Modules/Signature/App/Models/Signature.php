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

        return $query->where('signatures.status', 'pending')
            ->where(function ($q) use ($roles) {
                foreach ($roles as $role) {
                    $q->orWhereJsonContains('signatures.allowed_roles', $role);
                }
            })
            ->whereExists(function ($sub) use ($user) {
                $sub->selectRaw('1')
                    ->from('contracts')
                    ->whereColumn('contracts.id', 'signatures.signable_id')
                    ->whereNull('contracts.deleted_at')
                    ->where(function ($c) use ($user) {
                        $c->where('signatures.group', '!=', 'unit')
                          ->orWhere('contracts.unit_id', $user->unit_id);
                    })
                    ->where(function ($c) use ($user) {
                        $c->where('signatures.group', '!=', 'contractor')
                          ->orWhere('contracts.party_id', $user->party_id);
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
