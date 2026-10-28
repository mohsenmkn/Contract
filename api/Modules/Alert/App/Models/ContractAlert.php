<?php

namespace Modules\Alert\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contract\App\Models\Contract;

class ContractAlert extends Model
{
    protected $table = 'contract_alerts';

    protected $fillable = [
        'contract_id',
        'alert_type',
        'alert_date',
        'days_before',
        'message',
        'notified_at',
        'is_active',
    ];

    protected $casts = [
        'alert_date' => 'date',
        'notified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->whereNull('notified_at')
            ->where('alert_date', '<=', now()->addDays(7))
            ->active();
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('alert_date', today())
            ->active();
    }
}
