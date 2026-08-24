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

    public function test_store_works_on_private_disk_for_production_photos(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('produksi.jpg', 1200, 800);

        [$path, $thumbPath] = ImageUploader::store($file, 'production/1', 'local');

        Storage::disk('local')->assertExists($path);
        Storage::disk('local')->assertExists($thumbPath);
    }
}
