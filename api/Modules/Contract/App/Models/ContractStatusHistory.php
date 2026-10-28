<?php

namespace Modules\Contract\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\App\Models\User;

class ContractStatusHistory extends Model
{
    protected $table = 'contract_status_history';

    protected $fillable = [
        'contract_id',
        'from_status',
        'to_status',
        'user_id',
        'comments',
        'ip_address',
        'user_agent',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFromStatusLabelAttribute(): string
    {
        return Contract::STATUSES[$this->from_status] ?? $this->from_status ?? '—';
    }

    public function getToStatusLabelAttribute(): string
    {
        return Contract::STATUSES[$this->to_status] ?? $this->to_status;
    }
}
