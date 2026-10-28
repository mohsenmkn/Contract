<?php

namespace Modules\Payment\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Invoice\App\Models\Invoice;
use Modules\Document\App\Models\Document;
use Modules\Auth\App\Models\User;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'payment_number',
        'amount',
        'currency',
        'payment_date',
        'method',
        'reference_number',
        'check_number',
        'check_due_date',
        'status',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'check_due_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
