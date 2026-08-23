<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageUploader
{
    /**
     * Store an image on the given disk, resized to a sane maximum,
     * plus a small thumbnail. Returns [path, thumbPath].
     */
    public static function store(UploadedFile $file, string $dir, string $disk = 'public', int $maxWidth = 1600, int $thumbWidth = 480): array
    {
        $manager = new ImageManager(new Driver);
        $name = now()->format('YmdHis').'-'.Str::random(8);

        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: $maxWidth);
        $path = $dir.'/'.$name.'.webp';
        Storage::disk($disk)->put($path, (string) $image->toWebp(82));

        $thumb = $manager->read($file->getRealPath());
        $thumb->scaleDown(width: $thumbWidth);
        $thumbPath = $dir.'/'.$name.'-thumb.webp';
        Storage::disk($disk)->put($thumbPath, (string) $thumb->toWebp(75));

        return [$path, $thumbPath];
    }

    public static function delete(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
