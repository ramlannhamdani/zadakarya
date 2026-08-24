<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageInterface;
use Intervention\Image\ImageManager;

class ImageUploader
{
    /**
     * Store an image on the given disk, resized to a sane maximum,
     * plus a small thumbnail. Returns [path, thumbPath].
     */
    public static function store(UploadedFile $file, string $dir, string $disk = 'public', int $maxWidth = 1600, int $thumbWidth = 480): array
    {
        if (! extension_loaded('gd')) {
            // GD tidak tersedia di server: simpan file apa adanya, tanpa resize/thumbnail.
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $name = now()->format('YmdHis').'-'.Str::random(8);
            $path = $dir.'/'.$name.'.'.$ext;
            Storage::disk($disk)->putFileAs($dir, $file, $name.'.'.$ext);

            return [$path, $path];
        }

        $manager = new ImageManager(new Driver);
        $name = now()->format('YmdHis').'-'.Str::random(8);
        $ext = self::preferredExtension();

        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: $maxWidth);
        $path = $dir.'/'.$name.'.'.$ext;
        Storage::disk($disk)->put($path, self::encode($image, $ext, 82));

        $thumb = $manager->read($file->getRealPath());
        $thumb->scaleDown(width: $thumbWidth);
        $thumbPath = $dir.'/'.$name.'-thumb.'.$ext;
        Storage::disk($disk)->put($thumbPath, self::encode($thumb, $ext, 75));

        return [$path, $thumbPath];
    }

    public static function delete(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    /** WebP jika didukung GD server; jika tidak, fallback ke JPEG. */
    private static function preferredExtension(): string
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }

    private static function encode(ImageInterface $image, string $ext, int $quality): string
    {
        return (string) ($ext === 'webp' ? $image->toWebp($quality) : $image->toJpeg($quality));
    }
}
