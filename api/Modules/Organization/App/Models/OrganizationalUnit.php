<?php

namespace Modules\Organization\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Contract\App\Models\Contract;

class OrganizationalUnit extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * نماینده واحد
     */
    public function representative(): HasOne
    {
        return $this->hasOne(UnitRepresentative::class)->where('is_active', true);
    }

    /**
     * کاربر نماینده
     */
    public function representativeUser()
    {
        return $this->representative()->with('user');
    }

    /**
     * قراردادهای این واحد
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Scope: واحدهای فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * آیا این واحد نماینده دارد؟
     */
    public function hasRepresentative(): bool
    {
        return $this->representative()->exists();
    }

    /**
     * دریافت نام نماینده
     */
    public function getRepresentativeName(): ?string
    {
        $rep = $this->representative()->with('user')->first();
        return $rep?->user?->name;
    }
}
