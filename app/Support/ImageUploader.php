<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncoderInterface;

class ImageUploader
{
    /**
     * Store an image on the given disk, resized to a sane maximum,
     * plus a small thumbnail. Returns [path, thumbPath].
     */
    public static function store(UploadedFile $file, string $dir, string $disk = 'public', int $maxWidth = 1600, int $thumbWidth = 480): array
    {
        $name = now()->format('YmdHis').'-'.Str::random(8);

        if (! extension_loaded('gd')) {
            // GD tidak tersedia di server: simpan file apa adanya, tanpa resize/thumbnail.
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $path = $dir.'/'.$name.'.'.$ext;
            Storage::disk($disk)->putFileAs($dir, $file, $name.'.'.$ext);

            return [$path, $path];
        }

        return self::process($file->getRealPath(), $name, $dir, $disk, $maxWidth, $thumbWidth);
    }

    /**
     * Salin gambar yang sudah ada di disk public (mis. item Galeri) ke lokasi
     * entity lain, diproses ulang sesuai ukuran target. Returns [path, thumbPath].
     */
    public static function fromExisting(string $publicPath, string $dir, string $disk = 'public', int $maxWidth = 1600, int $thumbWidth = 480): array
    {
        $name = now()->format('YmdHis').'-'.Str::random(8);

        if (! extension_loaded('gd')) {
            $ext = pathinfo($publicPath, PATHINFO_EXTENSION) ?: 'jpg';
            $path = $dir.'/'.$name.'.'.$ext;
            Storage::disk($disk)->put($path, Storage::disk('public')->get($publicPath));

            return [$path, $path];
        }

        return self::process(Storage::disk('public')->path($publicPath), $name, $dir, $disk, $maxWidth, $thumbWidth);
    }

    /** Proses pilihan media picker (id item Galeri) menjadi file milik entity. */
    public static function fromGalleryId(mixed $id, string $dir, string $disk = 'public', int $maxWidth = 1600, int $thumbWidth = 480): ?array
    {
        $item = \App\Models\GalleryItem::find($id);

        return $item ? self::fromExisting($item->image_path, $dir, $disk, $maxWidth, $thumbWidth) : null;
    }

    private static function process(string $realPath, string $name, string $dir, string $disk, int $maxWidth, int $thumbWidth): array
    {
        $manager = new ImageManager(new Driver);
        $ext = self::preferredExtension();

        $image = $manager->decodePath($realPath);
        $image->scaleDown(width: $maxWidth);
        $path = $dir.'/'.$name.'.'.$ext;
        Storage::disk($disk)->put($path, (string) $image->encode(self::encoder($ext, 82)));

        $thumb = $manager->decodePath($realPath);
        $thumb->scaleDown(width: $thumbWidth);
        $thumbPath = $dir.'/'.$name.'-thumb.'.$ext;
        Storage::disk($disk)->put($thumbPath, (string) $thumb->encode(self::encoder($ext, 75)));

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

    private static function encoder(string $ext, int $quality): EncoderInterface
    {
        return $ext === 'webp'
            ? new WebpEncoder(quality: $quality)
            : new JpegEncoder(quality: $quality);
    }
}
