<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpreadsheetIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->customer = Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '08123456789',
        ]);
    }

    public function test_guest_cannot_access_spreadsheet_page(): void
    {
        $response = $this->get(route('admin.spreadsheet.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_spreadsheet_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.spreadsheet.index'));
        $response->assertOk();
        $response->assertSee('Integrasi Google Spreadsheet');
        $response->assertSee('Panduan Setup Google Spreadsheet');
    }

    public function test_admin_can_save_webhook_url_and_auto_sync_setting(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_test_123/exec';

        $response = $this->actingAs($this->admin)->patch(route('admin.spreadsheet.update'), [
            'google_sheet_webhook_url' => $webhookUrl,
            'google_sheet_auto_sync' => '1',
        ]);

        $response->assertRedirect(route('admin.spreadsheet.index'));
        $this->assertSame($webhookUrl, Setting::get('google_sheet_webhook_url'));
        $this->assertSame('1', Setting::get('google_sheet_auto_sync'));
    }

    public function test_test_connection_action(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_test_123/exec';
        Setting::set('google_sheet_webhook_url', $webhookUrl);

        Http::fake([
            $webhookUrl => Http::response(['success' => true, 'message' => 'Koneksi Berhasil!'], 200),
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.spreadsheet.test'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Koneksi Berhasil!',
        ]);

        $this->assertNotNull(Setting::get('google_sheet_last_connected_at'));
    }

    public function test_setup_sheet_action(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_test_123/exec';
        Setting::set('google_sheet_webhook_url', $webhookUrl);

        Http::fake([
            $webhookUrl => Http::response(['success' => true, 'message' => 'Setup Berhasil!'], 200),
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.spreadsheet.setup'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Setup Berhasil!',
        ]);
    }

    public function test_sync_all_data_action(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_test_123/exec';
        Setting::set('google_sheet_webhook_url', $webhookUrl);

        $order = Order::create([
            'order_number' => 'ZK-2609-001',
            'customer_id' => $this->customer->id,
            'name' => 'Polo Shirt 50 Pcs',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 4500000,
            'discount' => 0,
            'grand_total' => 4500000,
            'amount_paid' => 2000000,
            'payment_status' => 'partial',
        ]);

        OrderCost::create([
            'order_id' => $order->id,
            'category' => 'kain',
            'description' => 'Kain Lacoste Pique',
            'quantity' => 15,
            'unit' => 'kg',
            'unit_price' => 120000,
            'amount' => 1800000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 2000000,
            'payment_date' => now(),
            'method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        Http::fake([
            $webhookUrl => Http::response(['success' => true, 'message' => 'Sync all OK'], 200),
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.spreadsheet.sync'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'counts' => [
                'orders' => 1,
                'costs' => 1,
                'payments' => 1,
            ],
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['action'])
                && $data['action'] === 'sync_all'
                && count($data['orders']) === 1
                && $data['orders'][0]['order_number'] === 'ZK-2609-001'
                && count($data['costs']) === 1
                && count($data['payments']) === 1;
        });
    }

    public function test_creating_order_cost_triggers_auto_sync_when_configured(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_test_123/exec';
        Setting::set('google_sheet_webhook_url', $webhookUrl);
        Setting::set('google_sheet_auto_sync', '1');

        $order = Order::create([
            'order_number' => 'ZK-2609-002',
            'customer_id' => $this->customer->id,
            'name' => 'Jaket Bomber 30 Pcs',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 6000000,
            'discount' => 0,
            'grand_total' => 6000000,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        Http::fake([
            $webhookUrl => Http::response(['success' => true], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.costs.store', $order), [
            'category' => 'makloon',
            'description' => 'Bordir Komputer Logo Dada & Punggung',
            'quantity' => 30,
            'unit' => 'pcs',
            'unit_price' => 15000,
            'amount' => 450000,
            'spent_at' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_costs', [
            'description' => 'Bordir Komputer Logo Dada & Punggung',
            'amount' => 450000,
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return ($data['type'] ?? '') === 'cost'
                && ($data['cost']['description'] ?? '') === 'Bordir Komputer Logo Dada & Punggung'
                && ($data['cost']['amount'] ?? 0) === 450000;
        });
    }

    public function test_sync_failure_does_not_break_app_operations(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_test_123/exec';
        Setting::set('google_sheet_webhook_url', $webhookUrl);
        Setting::set('google_sheet_auto_sync', '1');

        $order = Order::create([
            'order_number' => 'ZK-2609-003',
            'customer_id' => $this->customer->id,
            'name' => 'Seragam Kemeja 20 Pcs',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 3000000,
            'discount' => 0,
            'grand_total' => 3000000,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        // Mock network error
        Http::fake([
            $webhookUrl => Http::response('Gateway Timeout', 504),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 1000000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
            'note' => 'DP 1jt',
        ]);

        // Should still succeed and redirect back without 500 error!
        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 1000000,
        ]);
    }
}
