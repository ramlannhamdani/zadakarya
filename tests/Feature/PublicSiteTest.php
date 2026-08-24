<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\User;
use App\Support\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $customer = Customer::create(['name' => 'Budi']);

        $order = DB::transaction(fn () => Order::create([
            'order_number' => Sequence::orderNumber(),
            'customer_id' => $customer->id,
            'name' => 'Seragam Kantor',
        ]));

        $order->items()->create(['product_name' => 'Kemeja', 'quantity' => 50, 'unit' => 'pcs', 'unit_price' => 90000, 'total' => 4500000]);
        $order->createInitialStages();
        $order->refreshTotals();

        return $order;
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/layanan', '/portfolio', '/blog', '/tentang-kami', '/kontak', '/konsultasi', '/tracking'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_tracking_finds_order_and_shows_all_seven_stages(): void
    {
        $order = $this->makeOrder();

        $response = $this->get('/tracking?order='.$order->order_number);

        $response->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Pesanan Diterima')
            ->assertSee('Desain Disetujui')
            ->assertSee('Bahan Disiapkan')
            ->assertSee('Proses Produksi')
            ->assertSee('Quality Check')
            ->assertSee('Siap Kirim')
            ->assertSee('Selesai');
    }

    public function test_tracking_shows_not_found_for_unknown_number(): void
    {
        $this->get('/tracking?order=ZDK-9999')->assertOk()->assertSee('tidak ditemukan');
    }

    public function test_tracking_never_exposes_internal_notes(): void
    {
        $order = $this->makeOrder();
        $order->update(['notes' => 'RAHASIA-MARGIN-40PERSEN']);

        $this->get('/tracking?order='.$order->order_number)
            ->assertOk()
            ->assertDontSee('RAHASIA-MARGIN-40PERSEN');
    }

    public function test_internal_production_photos_are_not_served_publicly(): void
    {
        $order = $this->makeOrder();
        $internal = $order->productionPhotos()->create([
            'stage_number' => 4,
            'image_path' => 'production/x.webp',
            'visibility' => 'internal',
        ]);

        $this->get(route('tracking.photo', $internal))->assertNotFound();
    }

    public function test_tracking_lists_ongoing_orders_without_sensitive_data(): void
    {
        $this->makeOrder();
        $second = $this->makeOrder();

        $response = $this->get('/tracking');

        $response->assertOk()
            ->assertSee('Sedang Kami Kerjakan')
            ->assertSee('Kemeja')                     // nama produk boleh tampil
            ->assertDontSee($second->order_number)    // nomor pesanan TIDAK boleh tampil di daftar
            ->assertDontSee('Seragam Kantor')         // nama proyek tidak boleh tampil
            ->assertDontSee('Budi');                  // nama customer tidak boleh tampil
    }

    public function test_ongoing_list_hides_completed_orders_and_respects_setting(): void
    {
        $order = $this->makeOrder();
        $order->update(['status' => 'completed']);

        $this->get('/tracking')->assertOk()->assertDontSee('Sedang Kami Kerjakan');

        $order->update(['status' => 'active']);
        \App\Models\Setting::set('show_ongoing', '0');

        $this->get('/tracking')->assertOk()->assertDontSee('Sedang Kami Kerjakan');
    }

    public function test_consultation_form_stores_inquiry(): void
    {
        $response = $this->post('/konsultasi', [
            'name' => 'Siti',
            'whatsapp' => '081234567890',
            'description' => 'Butuh 100 kaos sablon.',
        ]);

        $response->assertRedirect(route('consultation.create'));
        $this->assertDatabaseHas('inquiries', ['name' => 'Siti', 'status' => 'new']);
    }

    public function test_consultation_honeypot_blocks_bots(): void
    {
        $this->post('/konsultasi', [
            'name' => 'Bot',
            'whatsapp' => '081234567890',
            'description' => 'spam',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, Inquiry::count());
    }

    public function test_admin_pages_require_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/orders')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('rahasia-123')]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'rahasia-123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
