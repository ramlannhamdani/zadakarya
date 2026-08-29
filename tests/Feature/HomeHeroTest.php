<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\HeroDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_the_hero_with_default_copy(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Kualitas Tanpa Kompromi');
        $response->assertSee('Buat Pesanan Sekarang');
        $response->assertSee('Pelanggan Puas');
        $response->assertSee(HeroDefaults::RATING_TEXT);
    }

    public function test_product_panel_lists_services_and_hides_itself_when_empty(): void
    {
        // Tanpa layanan terbit, panel tidak boleh muncul (bukan kartu kosong).
        $this->get(route('home'))->assertDontSee('Apa yang ingin', false);

        Service::create([
            'name' => 'Kaos & T-Shirt',
            'slug' => 'kaos-t-shirt',
            'short_description' => 'Sablon & bordir',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('Apa yang ingin', false);
        $response->assertSee('Kaos &amp; T-Shirt', false);
        $response->assertSee('Sablon &amp; bordir', false);
    }

    public function test_hero_copy_follows_the_settings(): void
    {
        Setting::set('hero_title', "Baris Satu\nBaris Dua");
        Setting::set('hero_title_accent', 'Aksen Kustom');
        Setting::set('hero_stats', "77 | Tahun Berdiri\n88 | Mitra");

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Aksen Kustom');
        $response->assertSee('Tahun Berdiri');
        $response->assertSee('Mitra');
        $response->assertDontSee(HeroDefaults::TITLE_ACCENT);
    }

    public function test_navbar_links_to_the_new_sections(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Cara Order');
        $response->assertSee('Testimoni');
        $response->assertSee('id="cara-order"', false);
    }

    public function test_stats_parser_reads_value_and_label(): void
    {
        $stats = HeroDefaults::stats("1000+ | Pelanggan Puas\n\n5000+ | Pesanan Selesai");

        $this->assertSame([
            ['value' => '1000+', 'label' => 'Pelanggan Puas'],
            ['value' => '5000+', 'label' => 'Pesanan Selesai'],
        ], $stats);

        // Maksimal lima item; input kosong memakai bawaan.
        $this->assertCount(5, HeroDefaults::stats("a|1\nb|2\nc|3\nd|4\ne|5\nf|6"));
        $this->assertCount(5, HeroDefaults::stats(''));
    }

    public function test_admin_can_save_hero_settings(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'company_name' => 'Zada Karya Production',
            'whatsapp' => '+62 812-0000-0000',
            'hero_badge' => 'Badge Baru',
            'hero_text' => 'Teks hero baru.',
            'hero_rating_text' => '5/5 dari 10 pelanggan',
            'hero_stats' => '10 | Tahun',
        ])->assertRedirect();

        $this->assertSame('Badge Baru', Setting::get('hero_badge'));
        $this->assertSame('5/5 dari 10 pelanggan', Setting::get('hero_rating_text'));

        $this->get(route('home'))->assertSee('Badge Baru');
    }
}
