<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Angka yang dikirim ke spreadsheet dipakai untuk memutuskan uang, jadi yang
 * diuji di sini bukan "terkirim atau tidak", melainkan apakah isinya benar.
 */
class SpreadsheetAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK = 'https://script.google.com/macros/s/AKfycb_test/exec';

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->customer = Customer::create(['name' => 'Bg Basta', 'company' => 'J. Motion']);

        Setting::set('google_sheet_webhook_url', self::WEBHOOK);
        Setting::set('google_sheet_auto_sync', '1');
    }

    /**
     * Sengaja tidak dipasang di setUp: Http::fake() menggabungkan stub, bukan
     * menggantikannya, sehingga stub pertama selalu menang dan tes yang butuh
     * respons gagal tidak akan pernah mendapatkannya.
     */
    private function fakeOk(): void
    {
        Http::fake([self::WEBHOOK => Http::response(['status' => 'success'], 200)]);
    }

    private function makeOrder(int $total = 10000000): Order
    {
        return Order::create([
            'order_number' => 'ZDK-0001-190926',
            'customer_id' => $this->customer->id,
            'name' => 'Kaos Polo',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => $total,
            'discount' => 0,
            'grand_total' => $total,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_dp_comes_from_the_payment_type_not_from_words_in_the_note(): void
    {
        $this->fakeOk();

        $order = $this->makeOrder();

        // Catatan yang sebelumnya menipu heuristik lama:
        // "Pelunasan setelah DP" mengandung "DP", "Uang muka" tidak.
        $order->payments()->create([
            'amount' => 3000000, 'type' => Payment::TYPE_DP,
            'payment_date' => now(), 'method' => 'transfer', 'note' => 'Uang muka',
        ]);
        $order->payments()->create([
            'amount' => 7000000, 'type' => Payment::TYPE_SETTLEMENT,
            'payment_date' => now(), 'method' => 'transfer', 'note' => 'Pelunasan setelah DP',
        ]);
        $order->refreshPaymentStatus();

        $row = app(GoogleSheetService::class)->formatOrderData($order->fresh(['payments', 'items', 'costs', 'customer']));

        $this->assertSame(3000000, $row['dp']);
        $this->assertSame(7000000, $row['settlement']);
        $this->assertSame(0, $row['remaining']);
    }

    public function test_recording_a_payment_defaults_to_dp_only_for_the_first_one(): void
    {
        $this->fakeOk();

        $order = $this->makeOrder();

        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 4000000, 'payment_date' => now()->toDateString(), 'method' => 'transfer',
        ]);
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 6000000, 'payment_date' => now()->toDateString(), 'method' => 'cash',
        ]);

        $types = $order->payments()->orderBy('id')->pluck('type')->all();
        $this->assertSame([Payment::TYPE_DP, Payment::TYPE_SETTLEMENT], $types);

        // Pilihan admin selalu menang atas tebakan default.
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 500000, 'payment_date' => now()->toDateString(),
            'method' => 'cash', 'type' => Payment::TYPE_DP,
        ]);
        $this->assertSame(Payment::TYPE_DP, $order->payments()->latest('id')->first()->type);
    }

    public function test_every_synced_row_carries_an_id_so_it_can_be_deleted_later(): void
    {
        $this->fakeOk();

        $order = $this->makeOrder();
        $payment = $order->payments()->create([
            'amount' => 1000000, 'type' => Payment::TYPE_DP,
            'payment_date' => now(), 'method' => 'transfer',
        ]);
        $cost = $order->costs()->create([
            'category' => 'bahan', 'description' => 'Kain', 'quantity' => 10,
            'unit' => 'meter', 'unit_price' => 50000, 'amount' => 500000, 'spent_at' => now(),
        ]);

        $service = app(GoogleSheetService::class);
        $order->load(['payments', 'costs', 'items', 'customer']);

        $this->assertSame('ORD-'.$order->id, $service->formatOrderData($order)['id']);
        $this->assertSame('PAY-'.$payment->id, $service->formatPaymentData($payment)['id']);
        $this->assertSame('CST-'.$cost->id, $service->formatCostData($cost)['id']);
    }

    public function test_deleting_an_order_tells_the_sheet_to_remove_its_rows(): void
    {
        $this->fakeOk();

        $order = $this->makeOrder();
        $payment = $order->payments()->create([
            'amount' => 1000000, 'type' => Payment::TYPE_DP,
            'payment_date' => now(), 'method' => 'transfer',
        ]);
        $cost = $order->costs()->create([
            'category' => 'bahan', 'description' => 'Kain', 'quantity' => 1,
            'unit' => 'roll', 'unit_price' => 500000, 'amount' => 500000, 'spent_at' => now(),
        ]);

        $this->actingAs($this->admin)->delete(route('admin.orders.destroy', $order))->assertRedirect();

        Http::assertSent(function ($request) use ($order, $payment, $cost) {
            $d = $request->data();

            return ($d['action'] ?? '') === 'delete'
                && ($d['entity'] ?? '') === 'order'
                && ($d['id'] ?? '') === 'ORD-'.$order->id
                && in_array('CST-'.$cost->id, $d['cost_ids'] ?? [], true)
                && in_array('PAY-'.$payment->id, $d['payment_ids'] ?? [], true);
        });
    }

    public function test_deleting_a_payment_removes_its_cash_row_instead_of_leaving_a_ghost(): void
    {
        $this->fakeOk();

        $order = $this->makeOrder();
        $payment = $order->payments()->create([
            'amount' => 2500000, 'type' => Payment::TYPE_DP,
            'payment_date' => now(), 'method' => 'transfer',
        ]);
        $order->refreshPaymentStatus();

        $this->actingAs($this->admin)->delete(route('admin.payments.destroy', $payment))->assertRedirect();

        Http::assertSent(fn ($request) => ($request->data()['action'] ?? '') === 'delete'
            && ($request->data()['entity'] ?? '') === 'payment'
            && ($request->data()['id'] ?? '') === 'PAY-'.$payment->id);
    }

    public function test_connection_test_rejects_a_google_login_page_served_with_http_200(): void
    {
        // Deployment yang aksesnya bukan "Anyone" membalas 200 berisi HTML login.
        Http::fake([self::WEBHOOK => Http::response('<html>Sign in - Google Accounts</html>', 200)]);

        $result = app(GoogleSheetService::class)->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Anyone', $result['message']);
    }

    public function test_a_failed_sync_is_recorded_and_shown_in_the_panel(): void
    {
        // Satu stub yang bisa berubah, karena fake() kedua tidak menggantikan yang pertama.
        $failing = true;
        Http::fake([self::WEBHOOK => function () use (&$failing) {
            return $failing
                ? Http::response('Gateway Timeout', 504)
                : Http::response(['status' => 'success'], 200);
        }]);

        $order = $this->makeOrder();
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 1000000, 'payment_date' => now()->toDateString(), 'method' => 'transfer',
        ])->assertRedirect();

        $this->assertNotEmpty(Setting::get('google_sheet_last_error'));

        $this->actingAs($this->admin)->get(route('admin.spreadsheet.index'))
            ->assertOk()
            ->assertSee('Sinkronisasi terakhir gagal', false)
            ->assertSee('504', false);

        // Sinkron yang berhasil menghapus peringatannya lagi.
        $failing = false;
        $this->actingAs($this->admin)->post(route('admin.spreadsheet.sync'))->assertRedirect();

        $this->assertEmpty(Setting::get('google_sheet_last_error'));
    }

    public function test_panel_warns_when_the_deployed_script_is_older_than_the_app(): void
    {
        $appVersion = \App\Support\GoogleAppsScriptCode::version();
        $this->assertNotNull($appVersion, 'code.js harus mencantumkan SCRIPT_VERSION.');

        // Google melaporkan versinya di setiap respons; tes koneksi menyimpannya.
        Http::fake([self::WEBHOOK => Http::response(
            ['status' => 'success', 'message' => 'aktif', 'version' => '2020-01-01'], 200
        )]);

        $this->actingAs($this->admin)->post(route('admin.spreadsheet.test'))->assertRedirect();
        $this->assertSame('2020-01-01', Setting::get('google_sheet_script_version'));

        $this->actingAs($this->admin)->get(route('admin.spreadsheet.index'))
            ->assertOk()
            ->assertSee('masih versi 2020-01-01', false)
            ->assertSee('New version', false);
    }

    public function test_panel_links_to_the_spreadsheet_the_script_reports(): void
    {
        $url = 'https://docs.google.com/spreadsheets/d/1AbCdEf_zadakarya/edit';

        // Sebelum skrip melaporkannya, aplikasi memang belum tahu alamatnya:
        // URL webhook tidak memuat id spreadsheet.
        $this->actingAs($this->admin)->get(route('admin.spreadsheet.index'))
            ->assertOk()
            ->assertDontSee('docs.google.com/spreadsheets', false) // belum ada tautan
            ->assertSee('dilaporkan', false);                      // tapi ada penjelasannya

        Http::fake([self::WEBHOOK => Http::response([
            'status' => 'success',
            'version' => \App\Support\GoogleAppsScriptCode::version(),
            'sheet_url' => $url,
            'sheet_name' => 'Laporan Zada Karya',
        ], 200)]);

        $this->actingAs($this->admin)->post(route('admin.spreadsheet.test'))->assertRedirect();
        $this->assertSame($url, Setting::get('google_sheet_url'));

        $this->actingAs($this->admin)->get(route('admin.spreadsheet.index'))
            ->assertOk()
            ->assertSee('Buka Spreadsheet', false)
            ->assertSee(e($url), false)
            ->assertSee('Laporan Zada Karya', false);
    }

    public function test_no_version_warning_when_both_sides_match(): void
    {
        $appVersion = \App\Support\GoogleAppsScriptCode::version();

        Http::fake([self::WEBHOOK => Http::response(
            ['status' => 'success', 'message' => 'aktif', 'version' => $appVersion], 200
        )]);

        $this->actingAs($this->admin)->post(route('admin.spreadsheet.test'));

        $this->actingAs($this->admin)->get(route('admin.spreadsheet.index'))
            ->assertOk()
            ->assertDontSee('masih versi', false);
    }

    public function test_rebuilding_the_layout_also_refills_the_data(): void
    {
        $this->fakeOk();

        $this->makeOrder();

        $this->actingAs($this->admin)->post(route('admin.spreadsheet.setup'))->assertRedirect();

        // Menata ulang mengosongkan tab, jadi sync_all harus menyusul otomatis.
        Http::assertSent(fn ($r) => ($r->data()['action'] ?? '') === 'setup');
        Http::assertSent(fn ($r) => ($r->data()['action'] ?? '') === 'sync_all');
    }
}
