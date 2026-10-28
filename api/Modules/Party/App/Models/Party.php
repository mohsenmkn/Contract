<?php

namespace Modules\Party\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contract\App\Models\Contract;
use Modules\Auth\App\Models\User;

class Party extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'national_id',
        'registration_number',
        'economic_code',
        'type',
        'role',
        'address',
        'phone',
        'email',
        'website',
        'bank_name',
        'bank_account',
        'iban',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * رابطه با User (کاربر مرتبط - نماینده شرکت)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * رابطه با Contract
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeContractors($query)
    {
        return $query->where('role', 'contractor');
    }

    public function scopeEmployers($query)
    {
        return $query->where('role', 'employer');
    }

    /**
     * Scope برای پیمانکاران بدون کاربر
     */
    public function scopeWithoutUser($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope برای پیمانکاران با کاربر
     */
    public function scopeWithUser($query)
    {
        return $query->whereNotNull('user_id');
    }
}
