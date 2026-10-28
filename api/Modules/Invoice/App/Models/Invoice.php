<?php

namespace Modules\Invoice\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Contract\App\Models\Contract;
use Modules\Payment\App\Models\Payment;
use Modules\Document\App\Models\Document;
use Modules\Auth\App\Models\User;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_id',
        'invoice_number',
        'invoice_period',
        'from_date',
        'to_date',
        'gross_amount',
        'deductions',
        'tax_amount',
        'net_amount',
        'status',
        'description',
        'submitted_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'gross_amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'approved']);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->net_amount - $this->paid_amount;
    }

    public function isFullyPaid(): bool
    {
        return $this->remaining_amount <= 0;
    }
}
