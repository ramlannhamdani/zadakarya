<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiolinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_biolink_page_renders_successfully(): void
    {
        $response = $this->get('/biolink');

        $response->assertOk();
        $response->assertSee('Zada Karya', false);
        $response->assertSee('Website');
        $response->assertSee('TikTok');
        $response->assertSee('YouTube');
        $response->assertSee('Chat Admin 1');
        $response->assertSee('Chat Admin 2');
        $response->assertSee('Lokasi Workshop');
    }

    public function test_biolink_contains_all_required_links_and_blocks(): void
    {
        Setting::set('company_name', 'Zada Karya Production');
        Setting::set('whatsapp', '+62 812-9100-2362');
        Setting::set('whatsapp_2', '+62 813-9876-5432');
        Setting::set('tiktok', 'https://www.tiktok.com/@zadakarya.official');
        Setting::set('youtube', 'https://www.youtube.com/@zadakaryaproduction');
        Setting::set('google_maps_url', 'https://maps.app.goo.gl/xyz123');
        Setting::set('instagram', 'https://www.instagram.com/zadakarya.id');

        $response = $this->get(route('biolink'));

        $response->assertOk();

        // Website block link
        $response->assertSee(route('home'), false);
        $response->assertSee('Lihat produk & layanan kami');

        // TikTok block link
        $response->assertSee('https://www.tiktok.com/@zadakarya.official', false);
        $response->assertSee('Konten proses, hasil & inspirasi');

        // YouTube block link
        $response->assertSee('https://www.youtube.com/@zadakaryaproduction', false);
        $response->assertSee('Video proses & cerita di balik karya');

        // Admin 1 and Admin 2 WhatsApp links
        $response->assertSee('6281291002362', false);
        $response->assertSee('6281398765432', false);
        $response->assertSee('Chat Admin 1');
        $response->assertSee('Chat Admin 2');
        $response->assertSee('Konsultasi & pemesanan');

        // Google Maps block link
        $response->assertSee('https://maps.app.goo.gl/xyz123', false);
        $response->assertSee('Lokasi Workshop');
        $response->assertSee('Lihat kami di Google Maps');

        // 4 Highlight Features
        $response->assertSee('Custom<br>Kaos', false);
        $response->assertSee('Sablon<br>Manual &amp; DTF', false);
        $response->assertSee('Bordir<br>Komputer', false);
        $response->assertSee('Satuan<br>&amp; Partai', false);

        // Footer Thank You and Socials
        $response->assertSee('Terima kasih', false);
        $response->assertSee('sudah mendukung karya lokal', false);
        $response->assertSee('Z A D A &nbsp; K A R Y A', false);
    }

    public function test_legacy_bio_and_links_redirect_to_biolink(): void
    {
        $this->get('/bio')
            ->assertStatus(301)
            ->assertRedirect(route('biolink'));

        $this->get('/links')
            ->assertStatus(301)
            ->assertRedirect(route('biolink'));
    }

    public function test_sitemap_includes_biolink(): void
    {
        $xml = $this->get(route('sitemap'))->assertOk()->getContent();
        $this->assertStringContainsString(route('biolink'), $xml);
    }

    public function test_admin_can_update_biolink_settings(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.settings.update'), [
                'company_name' => 'Zada Karya Custom Apparel',
                'whatsapp' => '+62 811-1111-2222',
                'whatsapp_2' => '+62 812-3333-4444',
                'tiktok' => 'https://www.tiktok.com/@zadabrand',
                'youtube' => 'https://www.youtube.com/@zadabrand',
                'google_maps_url' => 'https://maps.google.com/?q=workshop',
            ])
            ->assertRedirect();

        $this->assertSame('+62 811-1111-2222', Setting::get('whatsapp'));
        $this->assertSame('+62 812-3333-4444', Setting::get('whatsapp_2'));
        $this->assertSame('https://www.tiktok.com/@zadabrand', Setting::get('tiktok'));
        $this->assertSame('https://www.youtube.com/@zadabrand', Setting::get('youtube'));
        $this->assertSame('https://maps.google.com/?q=workshop', Setting::get('google_maps_url'));
    }
}
