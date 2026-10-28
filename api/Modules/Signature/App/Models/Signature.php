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
        'status',
        'comments',
        'signature_hash',
        'ip_address',
        'user_agent',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
