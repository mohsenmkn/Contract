<?php

namespace Modules\Amendment\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Contract\App\Models\Contract;
use Modules\Signature\App\Models\Signature;
use Modules\Document\App\Models\Document;
use Modules\Auth\App\Models\User;

class Amendment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_id',
        'amendment_number',
        'type',
        'old_amount',
        'new_amount',
        'old_end_date',
        'new_end_date',
        'description',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'old_end_date' => 'date',
        'new_end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['amount_change', 'days_change'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getAmountChangeAttribute(): ?float
    {
        if ($this->new_amount !== null && $this->old_amount !== null) {
            return (float) $this->new_amount - (float) $this->old_amount;
        }
        return null;
    }

    public function getDaysChangeAttribute(): ?int
    {
        if ($this->new_end_date && $this->old_end_date) {
            return $this->old_end_date->diffInDays($this->new_end_date, false);
        }
        return null;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
