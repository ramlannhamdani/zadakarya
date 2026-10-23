<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCashFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->customer = Customer::create(['name' => 'Komunitas Seragam Nusantara']);
    }

    public function test_order_with_dp_less_than_hpp_is_in_cash_deficit(): void
    {
        // Contoh kasus pengguna:
        // Nilai pesanan Rp 5.000.000, DP baru bayar Rp 2.000.000.
        // Biaya belanja bahan (HPP) Rp 3.000.000.
        // Nombok kas kantor = Rp 1.000.000.
        $order = Order::create([
            'order_number' => 'ZK-2610-001',
            'customer_id' => $this->customer->id,
            'name' => 'Seragam Kerja Lapangan 50 Pcs',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 5000000,
            'discount' => 0,
            'grand_total' => 5000000,
            'amount_paid' => 2000000,
            'payment_status' => 'partial',
        ]);

        OrderCost::create([
            'order_id' => $order->id,
            'category' => 'fabric',
            'description' => 'Kain Drill Unione 100 meter',
            'amount' => 3000000,
            'quantity' => 100,
            'unit' => 'meter',
            'unit_price' => 30000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        $order->load('costs');

        // Total Cost Rp 3.000.000
        $this->assertSame(3000000, $order->total_cost);
        // Net Cash Flow: DP (2jt) - HPP (3jt) = -1jt
        $this->assertSame(-1000000, $order->net_cash_flow);
        // Nombok kas (Cash Deficit) = 1jt
        $this->assertSame(1000000, $order->cash_deficit);
        $this->assertSame(0, $order->cash_surplus);
        $this->assertTrue($order->is_cash_deficit);

        // Gross Profit akuntansi tetap positif: 5jt - 3jt = 2jt (40%)
        $this->assertSame(2000000, $order->gross_profit);

        $meta = $order->cash_status_meta;
        $this->assertSame('deficit', $meta['status']);
        $this->assertSame('Nombok Kas Rp 1.000.000', $meta['label']);
        $this->assertSame('Nombok Rp 1.000.000', $meta['short_label']);
    }

    public function test_order_settlement_recovers_cash_advance_and_surplus(): void
    {
        $order = Order::create([
            'order_number' => 'ZK-2610-002',
            'customer_id' => $this->customer->id,
            'name' => 'Jaket Angkatan 40 Pcs',
            'status' => 'active',
            'current_stage' => 2,
            'subtotal' => 5000000,
            'discount' => 0,
            'grand_total' => 5000000,
            'amount_paid' => 2000000,
            'payment_status' => 'partial',
        ]);

        OrderCost::create([
            'order_id' => $order->id,
            'category' => 'fabric',
            'description' => 'Bahan Taslan Milky',
            'amount' => 3000000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        // Customer melunasi sisa tagihan Rp 3.000.000
        $order->update([
            'amount_paid' => 5000000,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
        $order->load('costs');

        // Saat lunas: net_cash_flow = +2jt, cash_deficit = 0, is_cash_deficit = false
        $this->assertSame(2000000, $order->net_cash_flow);
        $this->assertSame(0, $order->cash_deficit);
        $this->assertSame(2000000, $order->cash_surplus);
        $this->assertFalse($order->is_cash_deficit);

        $meta = $order->cash_status_meta;
        $this->assertSame('settled', $meta['status']);
        $this->assertSame('Lunas — Kas Aman', $meta['label']);
        $this->assertSame('Lunas', $meta['short_label']);
    }

    public function test_order_with_adequate_dp_is_self_funded(): void
    {
        // DP Rp 3.500.000, Belanja HPP baru Rp 2.000.000 -> Sisa DP Rp 1.500.000 (Kas aman)
        $order = Order::create([
            'order_number' => 'ZK-2610-003',
            'customer_id' => $this->customer->id,
            'name' => 'Kemeja PDL 30 Pcs',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 5000000,
            'discount' => 0,
            'grand_total' => 5000000,
            'amount_paid' => 3500000,
            'payment_status' => 'partial',
        ]);

        OrderCost::create([
            'order_id' => $order->id,
            'category' => 'fabric',
            'description' => 'Kain Ripstop',
            'amount' => 2000000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        $order->load('costs');

        $this->assertSame(1500000, $order->net_cash_flow);
        $this->assertSame(0, $order->cash_deficit);
        $this->assertSame(1500000, $order->cash_surplus);
        $this->assertFalse($order->is_cash_deficit);

        $meta = $order->cash_status_meta;
        $this->assertSame('surplus', $meta['status']);
        $this->assertSame('DP Cukup (Sisa Rp 1.500.000)', $meta['label']);
        $this->assertSame('DP Cukup', $meta['short_label']);
    }

    public function test_order_index_filters_cash_deficit_orders(): void
    {
        // 1 order nombok
        $order1 = Order::create([
            'order_number' => 'ZK-DEFICIT-01',
            'customer_id' => $this->customer->id,
            'name' => 'Pesanan Nombok',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 5000000,
            'discount' => 0,
            'grand_total' => 5000000,
            'amount_paid' => 1000000,
            'payment_status' => 'partial',
        ]);
        OrderCost::create([
            'order_id' => $order1->id,
            'category' => 'fabric',
            'description' => 'Belanja Bahan',
            'amount' => 2500000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        // 1 order aman (DP cukup)
        $order2 = Order::create([
            'order_number' => 'ZK-SAFE-02',
            'customer_id' => $this->customer->id,
            'name' => 'Pesanan Kas Aman',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 5000000,
            'discount' => 0,
            'grand_total' => 5000000,
            'amount_paid' => 3000000,
            'payment_status' => 'partial',
        ]);
        OrderCost::create([
            'order_id' => $order2->id,
            'category' => 'fabric',
            'description' => 'Belanja Bahan',
            'amount' => 1500000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['cash_status' => 'deficit']));

        $response->assertOk();
        $response->assertSee('ZK-DEFICIT-01');
        $response->assertDontSee('ZK-SAFE-02');
    }

    public function test_dashboard_and_google_sheet_format_includes_deficit(): void
    {
        $order = Order::create([
            'order_number' => 'ZK-DEFICIT-03',
            'customer_id' => $this->customer->id,
            'name' => 'Pesanan Uji Sheet',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 5000000,
            'discount' => 0,
            'grand_total' => 5000000,
            'amount_paid' => 1500000,
            'payment_status' => 'partial',
        ]);
        OrderCost::create([
            'order_id' => $order->id,
            'category' => 'fabric',
            'description' => 'Belanja Kain',
            'amount' => 2500000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        // Test Dashboard
        $dashResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Talangan Kas (Nombok)');
        $dashResponse->assertSee('1.000.000'); // 2.5jt - 1.5jt = 1jt

        // Test GoogleSheetService
        $service = new GoogleSheetService();
        $formatted = $service->formatOrderData($order->fresh(['costs', 'customer', 'items', 'payments']));

        $this->assertSame(-1000000, $formatted['cash_position']);
        $this->assertSame(1000000, $formatted['cash_deficit']);
        $this->assertStringContainsString('Nombok Kas', $formatted['status']);
    }
}
