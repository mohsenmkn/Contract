<?php

namespace Modules\Signature\App\Services;

use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\App\Models\User;
use Modules\Signature\App\Models\Signature;
use Modules\Signature\App\Models\SignatureAsset;

/**
 * مدیریت تصویر امضا:
 *  - ورودی PNG/JPEG اعتبارسنجی و با GD دوباره encode می‌شود (حذف متادیتا و payload مخرب)
 *  - فقط روی دیسک private ذخیره می‌شود (نه public / storage:link)
 *  - در لحظه‌ی امضا یک snapshot مستقل از تصویر پروفایل گرفته می‌شود
 *
 * نیازمند افزونه‌ی PHP: gd
 */
class SignatureImageService
{
    private const DISK = 'local';
    private const MAX_BYTES = 512 * 1024;      // 512KB
    private const MAX_SOURCE_DIMENSION = 4000; // جلوگیری از decompression bomb
    private const MAX_WIDTH = 600;
    private const MAX_HEIGHT = 200;
    private const ALLOWED_MIMES = ['image/png', 'image/jpeg'];

    /** ثبت (یا جایگزینی) تصویر امضای کاربر */
    public function store(User $owner, UploadedFile $file, string $kind = 'signature'): SignatureAsset
    {
        if (!$file->isValid() || $file->getSize() > self::MAX_BYTES) {
            throw new DomainException('حجم تصویر امضا باید حداکثر ۵۱۲ کیلوبایت باشد.');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new DomainException('فقط تصویر PNG یا JPEG مجاز است.');
        }

        $info = @getimagesize($file->getRealPath());
        if ($info === false
            || $info[0] > self::MAX_SOURCE_DIMENSION
            || $info[1] > self::MAX_SOURCE_DIMENSION) {
            throw new DomainException('ابعاد تصویر نامعتبر یا بیش از حد بزرگ است.');
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw new DomainException('فایل تصویر قابل پردازش نیست.');
        }

        $png = $this->normalize($source);
        $hash = hash('sha256', $png);
        $path = "signatures/assets/{$owner->getMorphClass()}/{$owner->getKey()}/{$hash}.png";

        Storage::disk(self::DISK)->put($path, $png);

        return DB::transaction(function () use ($owner, $kind, $path, $hash) {
            SignatureAsset::where('owner_type', $owner->getMorphClass())
                ->where('owner_id', $owner->getKey())
                ->where('kind', $kind)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return SignatureAsset::create([
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
                'kind' => $kind,
                'path' => $path,
                'hash' => $hash,
                'is_active' => true,
            ]);
        });
    }

    /** تصویر فعال کاربر؛ اگر نداشت خطا می‌دهد */
    public function activeFor(User $owner, string $kind = 'signature'): SignatureAsset
    {
        $asset = SignatureAsset::where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('kind', $kind)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (!$asset) {
            throw new DomainException('ابتدا تصویر امضای خود را در پروفایل ثبت کنید.');
        }

        return $asset;
    }

    /**
     * کپی تغییرناپذیر تصویر برای یک امضای مشخص.
     * اگر کاربر بعداً تصویرش را عوض کند، قراردادهای قبلی تغییر نمی‌کنند.
     *
     * @return array{path: string, hash: string}
     */
    public function snapshot(SignatureAsset $asset, Signature $slot): array
    {
        $disk = Storage::disk(self::DISK);

        if (!$disk->exists($asset->path)) {
            throw new DomainException('فایل تصویر امضا پیدا نشد؛ لطفاً دوباره ثبت کنید.');
        }

        $binary = $disk->get($asset->path);
        if (!hash_equals($asset->hash, hash('sha256', $binary))) {
            throw new DomainException('تمامیت تصویر امضا تأیید نشد؛ لطفاً دوباره ثبت کنید.');
        }

        $path = "signatures/snapshots/{$slot->signable_id}/{$slot->id}.png";
        $disk->put($path, $binary);

        return ['path' => $path, 'hash' => $asset->hash];
    }

    /**
     * تصویر امضای ثبت‌شده به‌صورت data URI برای نمایش پایین متن قرارداد (UI و PDF).
     * اگر تصویر دست‌کاری شده باشد null برمی‌گرداند.
     */
    public function dataUri(Signature $slot): ?string
    {
        if (!$slot->isSigned() || !$slot->image_path) {
            return null;
        }

        $disk = Storage::disk(self::DISK);
        if (!$disk->exists($slot->image_path)) {
            return null;
        }

        $binary = $disk->get($slot->image_path);
        if (!hash_equals((string) $slot->image_hash, hash('sha256', $binary))) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($binary);
    }

    /** تغییر اندازه با حفظ نسبت و شفافیت، خروجی PNG */
    private function normalize(\GdImage $source): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 255, 255, 255, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagepng($target);
        $binary = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        return $binary;
    }
}
