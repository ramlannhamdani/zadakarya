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

    public function test_hidden_gallery_items_do_not_appear_publicly(): void
    {
        GalleryItem::create(['image_path' => 'gallery/tampil.webp', 'thumb_path' => 'gallery/tampil-thumb.webp']);
        GalleryItem::create(['image_path' => 'gallery/rahasia.webp', 'thumb_path' => 'gallery/rahasia-thumb.webp', 'is_public' => false]);

        $this->get('/galeri')->assertOk()
            ->assertSee('tampil-thumb.webp')
            ->assertDontSee('rahasia-thumb.webp');
    }

    public function test_admin_can_toggle_gallery_visibility(): void
    {
        $admin = User::factory()->create();
        $item = GalleryItem::create(['image_path' => 'gallery/a.webp']);

        $this->assertTrue($item->is_public);

        $this->actingAs($admin)->patch(route('admin.gallery.toggle', $item))->assertRedirect();
        $this->assertFalse($item->fresh()->is_public);

        $this->actingAs($admin)->patch(route('admin.gallery.toggle', $item));
        $this->assertTrue($item->fresh()->is_public);
    }

    public function test_picker_endpoint_returns_gallery_items(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.gallery.store'), [
            'photos' => [UploadedFile::fake()->image('a.jpg', 600, 400)],
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.gallery.picker'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonStructure([['id', 'thumb']]);
    }

    public function test_media_picker_copies_gallery_image_to_entity(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.gallery.store'), [
            'photos' => [UploadedFile::fake()->image('a.jpg', 900, 600)],
        ]);
        $item = GalleryItem::first();

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'Layanan Uji Picker',
            'featured_image_pick' => $item->id,
        ])->assertRedirect();

        $service = \App\Models\Service::where('slug', 'layanan-uji-picker')->firstOrFail();
        $this->assertNotNull($service->featured_image);
        Storage::disk('public')->assertExists($service->featured_image);
        // Disalin ke folder entity, bukan referensi ke file galeri.
        $this->assertNotSame($item->image_path, $service->featured_image);
    }
}
