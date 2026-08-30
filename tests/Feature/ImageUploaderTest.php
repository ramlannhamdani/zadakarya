<?php

namespace Tests\Feature;

use App\Support\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploaderTest extends TestCase
{
    public function test_store_creates_resized_image_and_thumbnail(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('foto.png', 2000, 1500);

        [$path, $thumbPath] = ImageUploader::store($file, 'test-uploads');

        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists($thumbPath);
        $this->assertNotSame($path, $thumbPath);
        $this->assertGreaterThan(0, Storage::disk('public')->size($path));
        $this->assertGreaterThan(0, Storage::disk('public')->size($thumbPath));

        // Hasil harus benar-benar gambar valid dengan lebar sesuai batas.
        [$width] = getimagesizefromstring(Storage::disk('public')->get($path));
        [$thumbWidth] = getimagesizefromstring(Storage::disk('public')->get($thumbPath));
        $this->assertLessThanOrEqual(1600, $width);
        $this->assertLessThanOrEqual(480, $thumbWidth);
    }

    public function test_transparent_edges_are_detected_for_cutout_images(): void
    {
        $dir = sys_get_temp_dir().'/zdk-alpha-test';
        @mkdir($dir, 0777, true);

        // PNG dengan sudut transparan (ciri foto potongan).
        $cutout = imagecreatetruecolor(60, 60);
        imagesavealpha($cutout, true);
        imagefill($cutout, 0, 0, imagecolorallocatealpha($cutout, 0, 0, 0, 127));
        imagefilledellipse($cutout, 30, 30, 30, 30, imagecolorallocate($cutout, 120, 20, 10));
        imagepng($cutout, $dir.'/cutout.png');
        imagedestroy($cutout);

        // PNG opak penuh (foto biasa).
        $solid = imagecreatetruecolor(60, 60);
        imagefill($solid, 0, 0, imagecolorallocate($solid, 200, 200, 200));
        imagepng($solid, $dir.'/solid.png');
        imagedestroy($solid);

        $this->assertTrue(ImageUploader::hasTransparentEdges($dir.'/cutout.png'));
        $this->assertFalse(ImageUploader::hasTransparentEdges($dir.'/solid.png'));
        $this->assertFalse(ImageUploader::hasTransparentEdges($dir.'/tidak-ada.png'));

        @unlink($dir.'/cutout.png');
        @unlink($dir.'/solid.png');
        @rmdir($dir);
    }

    public function test_transparent_padding_is_trimmed_so_signatures_render_large(): void
    {
        $dir = sys_get_temp_dir().'/zdk-trim-test';
        @mkdir($dir, 0777, true);
        $path = $dir.'/ttd.png';

        // Kanvas 400x400 transparan dengan coretan kecil di tengah (100x60).
        $canvas = imagecreatetruecolor(400, 400);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);
        imagefilledrectangle($canvas, 150, 170, 250, 230, imagecolorallocate($canvas, 10, 10, 10));
        imagepng($canvas, $path);
        imagedestroy($canvas);

        $this->assertTrue(ImageUploader::trimTransparent($path, 0));

        [$width, $height] = getimagesize($path);
        $this->assertSame(101, $width);
        $this->assertSame(61, $height);

        // Gambar yang sudah rapat tidak diubah lagi.
        $this->assertFalse(ImageUploader::trimTransparent($path, 0));

        @unlink($path);
        @rmdir($dir);
    }

    public function test_store_works_on_private_disk_for_production_photos(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('produksi.jpg', 1200, 800);

        [$path, $thumbPath] = ImageUploader::store($file, 'production/1', 'local');

        Storage::disk('local')->assertExists($path);
        Storage::disk('local')->assertExists($thumbPath);
    }
}
