<?php

namespace Modules\Guarantee\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Contract\App\Models\Contract;
use Modules\Document\App\Models\Document;
use Modules\Auth\App\Models\User;

class Guarantee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_id',
        'guarantee_number',
        'type',
        'amount',
        'currency',
        'bank_name',
        'bank_branch',
        'issue_date',
        'expiry_date',
        'status',
        'description',
        'file_path',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function isExpiring(): bool
    {
        return $this->status === 'active'
            && $this->expiry_date->diffInDays(now()) <= 30;
    }
}
