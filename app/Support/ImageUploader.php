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

    /**
     * Apakah gambar punya area transparan di tepinya (ciri foto "potongan"/cut-out).
     * Dipakai untuk memilih gaya tampilan foto hero secara otomatis.
     */
    public static function hasTransparentEdges(string $absolutePath): bool
    {
        if (! extension_loaded('gd') || ! is_file($absolutePath)) {
            return false;
        }

        $mime = @getimagesize($absolutePath)['mime'] ?? '';

        $image = match ($mime) {
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($absolutePath) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => null,
        };

        if (! $image) {
            return false;
        }

        $w = imagesx($image) - 1;
        $h = imagesy($image) - 1;
        $points = [[0, 0], [$w, 0], [0, $h], [$w, $h], [intdiv($w, 2), 0]];
        $transparent = false;

        foreach ($points as [$x, $y]) {
            // Bit 24-30 pada warna GD = alpha (0 = opak, 127 = transparan penuh).
            if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 100) {
                $transparent = true;
                break;
            }
        }

        imagedestroy($image);

        return $transparent;
    }

    /**
     * Potong bingkai transparan di sekeliling gambar (mis. tanda tangan atau
     * stempel yang digambar di kanvas besar). Tanpa ini, tinggi tampil di PDF
     * terpakai oleh ruang kosong sehingga coretannya terlihat kecil.
     * Mengembalikan true bila file benar-benar dipotong.
     */
    public static function trimTransparent(string $absolutePath, int $padding = 4): bool
    {
        if (! extension_loaded('gd') || ! is_file($absolutePath)) {
            return false;
        }

        $mime = @getimagesize($absolutePath)['mime'] ?? '';

        $image = match ($mime) {
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($absolutePath) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => null,
        };

        if (! $image) {
            return false;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $minX = $w;
        $minY = $h;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                // Alpha GD: 0 = opak, 127 = transparan penuh.
                if ((((imagecolorat($image, $x, $y) >> 24) & 0x7F)) < 100) {
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }

        // Seluruhnya transparan, atau memang sudah rapat: biarkan apa adanya.
        if ($maxX < 0) {
            imagedestroy($image);

            return false;
        }

        $minX = max(0, $minX - $padding);
        $minY = max(0, $minY - $padding);
        $maxX = min($w - 1, $maxX + $padding);
        $maxY = min($h - 1, $maxY + $padding);
        $newW = $maxX - $minX + 1;
        $newH = $maxY - $minY + 1;

        if ($newW >= $w && $newH >= $h) {
            imagedestroy($image);

            return false;
        }

        $cropped = imagecreatetruecolor($newW, $newH);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
        imagecopy($cropped, $image, 0, 0, $minX, $minY, $newW, $newH);

        $saved = $mime === 'image/webp' && function_exists('imagewebp')
            ? imagewebp($cropped, $absolutePath, 92)
            : imagepng($cropped, $absolutePath);

        imagedestroy($image);
        imagedestroy($cropped);

        return (bool) $saved;
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
