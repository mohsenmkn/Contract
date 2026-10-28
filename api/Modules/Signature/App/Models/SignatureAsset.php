<?php

namespace Modules\Signature\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * تصویر امضا یا مهر که صاحبش (معمولاً User) یک بار ثبت می‌کند.
 * فایل روی دیسک private نگه داشته می‌شود و هرگز مستقیم سرو نمی‌شود.
 */
class SignatureAsset extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'kind',      // signature | seal
        'path',
        'hash',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = ['path'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
