<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductionPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_lives_at_the_indexed_indonesian_slug(): void
    {
        $this->assertSame(url('/lacak-pesanan'), route('tracking.index'));

        $this->get('/lacak-pesanan')->assertOk()->assertSee('Lacak', false);
    }

    public function test_old_tracking_address_redirects_permanently(): void
    {
        $this->get('/tracking')
            ->assertStatus(301)
            ->assertRedirect(route('tracking.index'));
    }

    public function test_tracking_links_already_sent_to_customers_keep_their_order_number(): void
    {
        // Pesan WhatsApp yang terlanjur terkirim berbentuk /tracking?order=ZDK-...
        // Nomornya harus ikut pindah, kalau tidak customer mendarat di form kosong.
        $this->get('/tracking?order=ZDK-0001-060926')
            ->assertStatus(301)
            ->assertRedirect(route('tracking.index', ['order' => 'ZDK-0001-060926']));
    }

    public function test_old_production_photo_address_redirects_to_the_same_photo(): void
    {
        $customer = Customer::create(['name' => 'Budi']);
        $order = Order::create([
            'order_number' => 'ZDK-0001-060926',
            'customer_id' => $customer->id,
            'name' => 'Seragam',
            'status' => 'active',
            'current_stage' => 1,
        ]);
        $photo = ProductionPhoto::create([
            'order_id' => $order->id,
            'image_path' => 'orders/foto.jpg',
            'stage_number' => 4,
            'visibility' => 'public',
        ]);

        $this->get('/tracking/foto/'.$photo->id)
            ->assertStatus(301)
            ->assertRedirect(route('tracking.photo', $photo));
    }

    public function test_other_legacy_spellings_land_on_the_right_page(): void
    {
        $map = [
            '/lacak' => '/lacak-pesanan',
            '/cek-pesanan' => '/lacak-pesanan',
            '/portofolio' => '/portfolio',
            '/gallery' => '/galeri',
            '/artikel' => '/blog',
            '/produk' => '/layanan',
            '/jasa' => '/layanan',
            '/tentang' => '/tentang-kami',
            '/hubungi-kami' => '/kontak',
        ];

        foreach ($map as $old => $new) {
            $this->get($old)->assertStatus(301)->assertRedirect(url($new));
            $this->get($new)->assertOk(); // tujuannya memang hidup, bukan 404 lain
        }
    }

    public function test_sitemap_advertises_the_new_slug_only(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/lacak-pesanan', $xml);
        $this->assertStringNotContainsString('<loc>'.url('/tracking').'</loc>', $xml);
    }
}
