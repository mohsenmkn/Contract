<?php

namespace Modules\Organization\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\App\Models\User;

class UnitRepresentative extends Model
{
    protected $fillable = [
        'unit_id',
        'user_id',
        'is_active',
        'assigned_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    /**
     * واحد سازمانی
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /**
     * کاربر نماینده
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: نمایندگان فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * آیا این نماینده فعال است؟
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->unit?->is_active && $this->user?->is_active;
    }
}
