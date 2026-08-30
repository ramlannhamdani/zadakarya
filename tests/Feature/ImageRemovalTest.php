<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageRemovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    /** Isian wajib halaman Pengaturan. */
    private function settingsPayload(array $extra = []): array
    {
        return array_merge([
            'company_name' => 'Zada Karya Production',
            'whatsapp' => '+62 812-0000-0000',
        ], $extra);
    }

    public function test_saving_settings_without_remove_flags_keeps_every_image(): void
    {
        Storage::fake('public');

        $images = [
            'logo' => 'branding/logo.webp',
            'logo_light' => 'branding/logo-putih.webp',
            'favicon' => 'branding/favicon.png',
            'hero_image' => 'hero/model.webp',
            'invoice_signature' => 'branding/ttd.webp',
            'invoice_stamp' => 'branding/stempel.webp',
            'workshop_photo_1' => 'workshop/1.webp',
            'workshop_photo_2' => 'workshop/2.webp',
            'workshop_photo_3' => 'workshop/3.webp',
        ];

        foreach ($images as $key => $path) {
            Storage::disk('public')->put($path, 'x');
            Setting::set($key, $path);
        }

        // Simpan form seperti biasa (mis. hanya mengubah teks) — tanpa flag hapus apa pun.
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $this->settingsPayload(['tagline' => 'Tagline baru']))
            ->assertRedirect();

        foreach ($images as $key => $path) {
            $this->assertSame($path, Setting::get($key), "Gambar {$key} seharusnya tidak ikut terhapus.");
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_remove_input_is_not_submitted_until_the_x_button_is_used(): void
    {
        Setting::set('logo', 'branding/logo.webp');

        $html = $this->actingAs($this->admin)->get(route('admin.settings.edit'))->assertOk()->getContent();

        // Input penghapus harus berada di dalam <template> Alpine, bukan di DOM
        // aktif — elemen tersembunyi biasa tetap ikut terkirim saat submit.
        $position = strpos($html, 'name="remove_logo"');
        $this->assertNotFalse($position);

        $before = substr($html, 0, $position);
        $this->assertStringEndsWith(
            '<template x-if="removed">',
            trim(substr($before, strrpos($before, '<template'), strpos($before, '>', strrpos($before, '<template')) - strrpos($before, '<template') + 1))
        );
    }

    public function test_setting_image_can_be_emptied_with_the_remove_flag(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/logo-lama.webp', 'x');
        Setting::set('logo', 'branding/logo-lama.webp');

        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $this->settingsPayload(['remove_logo' => 1]))
            ->assertRedirect();

        $this->assertNull(Setting::get('logo'));
        Storage::disk('public')->assertMissing('branding/logo-lama.webp');
    }

    public function test_uploading_a_replacement_wins_over_the_remove_flag(): void
    {
        Storage::fake('public');
        Setting::set('invoice_stamp', 'branding/stempel-lama.png');

        $this->actingAs($this->admin)->patch(route('admin.settings.update'), $this->settingsPayload([
            'remove_invoice_stamp' => 1,
            'invoice_stamp' => UploadedFile::fake()->image('stempel-baru.png', 300, 300),
        ]))->assertRedirect();

        // Gambar pengganti tetap terpasang, bukan ikut terhapus.
        $this->assertNotNull(Setting::get('invoice_stamp'));
        $this->assertNotSame('branding/stempel-lama.png', Setting::get('invoice_stamp'));
    }

    public function test_removing_the_hero_image_resets_its_display_style(): void
    {
        Storage::fake('public');
        Setting::set('hero_image', 'hero/foto.webp');
        Setting::set('hero_image_style', 'cutout');

        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $this->settingsPayload(['remove_hero_image' => 1]))
            ->assertRedirect();

        $this->assertNull(Setting::get('hero_image'));
        $this->assertSame('framed', Setting::get('hero_image_style'));
    }

    public function test_service_and_article_images_can_be_emptied(): void
    {
        Storage::fake('public');

        $service = Service::create([
            'name' => 'Polo Shirt', 'slug' => 'polo-shirt', 'is_published' => true,
            'featured_image' => 'services/foto.webp',
        ]);
        $article = Article::create([
            'title' => 'Tips Bahan', 'slug' => 'tips-bahan', 'content' => '<p>Isi</p>',
            'is_published' => true, 'featured_image' => 'articles/foto.webp',
        ]);

        $this->actingAs($this->admin)->patch(route('admin.services.update', $service), [
            'name' => 'Polo Shirt', 'slug' => 'polo-shirt', 'remove_featured_image' => 1,
        ])->assertRedirect();

        $this->actingAs($this->admin)->patch(route('admin.articles.update', $article), [
            'title' => 'Tips Bahan', 'slug' => 'tips-bahan', 'content' => '<p>Isi</p>', 'remove_featured_image' => 1,
        ])->assertRedirect();

        $this->assertNull($service->fresh()->featured_image);
        $this->assertNull($article->fresh()->featured_image);
    }
}
