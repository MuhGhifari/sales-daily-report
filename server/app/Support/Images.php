<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Photos arrive from the browser as data URLs (already cropped and resized there). */
class Images
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public static function isDataUrl(?string $v): bool
    {
        return is_string($v) && str_starts_with($v, 'data:image/');
    }

    /**
     * Saves a JPEG/PNG/WebP data URL under storage/app/public/{folder} and returns its path
     * relative to the site root (e.g. "storage/photos/abc.jpg"), which is what the pages store.
     */
    public static function store(string $dataUrl, string $folder): string
    {
        if (! preg_match('#^data:image/(jpeg|png|webp);base64,(.+)$#s', $dataUrl, $m)) {
            throw ValidationException::withMessages(['photo' => 'Format foto harus JPG, PNG atau WebP.']);
        }
        $bytes = base64_decode($m[2], true);
        if ($bytes === false || strlen($bytes) > self::MAX_BYTES || ! @getimagesizefromstring($bytes)) {
            throw ValidationException::withMessages(['photo' => 'Foto tidak valid atau terlalu besar (maks. 2 MB).']);
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        // Re-encode when GD is available, which also drops anything hidden in the file
        if (function_exists('imagecreatefromstring') && ($img = @imagecreatefromstring($bytes))) {
            ob_start();
            $ext === 'png' ? imagepng($img, null, 6) : imagejpeg($img, null, 85);
            $bytes = ob_get_clean();
            $ext = $ext === 'png' ? 'png' : 'jpg';
        }
        $path = $folder.'/'.Str::random(24).'.'.$ext;
        Storage::disk('public')->put($path, $bytes);

        return 'storage/'.$path;
    }

    /** Deletes a previously uploaded file (paths not under storage/ are bundled assets and stay). */
    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }
}
