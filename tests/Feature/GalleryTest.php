<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_gallery_page_renders(): void
    {
        $this->get('/galeri')->assertOk()->assertSee('Dokumentasi Produksi');
    }

    public function test_admin_can_upload_and_delete_gallery_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.gallery.store'), [
            'photos' => [
                UploadedFile::fake()->image('a.jpg', 800, 1200),
                UploadedFile::fake()->image('b.png', 1200, 800),
            ],
        ])->assertRedirect();

        $this->assertSame(2, GalleryItem::count());

        $item = GalleryItem::first();
        Storage::disk('public')->assertExists($item->image_path);
        Storage::disk('public')->assertExists($item->thumb_path);

        $this->actingAs($admin)->delete(route('admin.gallery.destroy', $item))->assertRedirect();

        $this->assertSame(1, GalleryItem::count());
        Storage::disk('public')->assertMissing($item->image_path);
    }

    public function test_guests_cannot_upload_to_gallery(): void
    {
        $this->post(route('admin.gallery.store'), [])->assertRedirect(route('admin.login'));
    }
}
