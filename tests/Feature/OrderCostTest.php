<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderCostTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->customer = Customer::create(['name' => 'PT Sumber Rejeki']);

        $this->order = Order::create([
            'order_number' => 'ZK-2609-001',
            'customer_id' => $this->customer->id,
            'name' => 'Kaos Gathering 100 Pcs',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 6000000,
            'discount' => 0,
            'grand_total' => 6000000,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        $this->order->items()->create([
            'product_name' => 'Kaos Combed 24s Sablon',
            'quantity' => 100,
            'unit' => 'pcs',
            'unit_price' => 60000,
            'total' => 6000000,
            'sort_order' => 0,
        ]);
    }

    public function test_can_record_production_cost_and_calculates_profitability(): void
    {
        Storage::fake('local');

        // Belum ada biaya: total_cost = 0, profit_status = 'unset'
        $this->assertSame(0, $this->order->total_cost);
        $this->assertSame('unset', $this->order->profit_status);

        $receipt = UploadedFile::fake()->create('struk_kain.jpg', 200, 'image/jpeg');

        $response = $this->actingAs($this->admin)->post(route('admin.orders.costs.store', $this->order), [
            'category' => 'kain',
            'description' => 'Kain Combed 24s Hitam Reaktif 25 kg',
            'quantity' => '25,5',
            'unit' => 'kg',
            'amount' => '2.500.000', // with thousand separator
            'spent_at' => '2026-09-15',
            'receipt' => $receipt,
        ]);

        $response->assertRedirect(route('admin.orders.show', [$this->order, 'tab' => 'hpp']));

        $cost = OrderCost::firstOrFail();
        $this->assertSame($this->order->id, $cost->order_id);
        $this->assertSame('kain', $cost->category);
        $this->assertSame('Kain Combed 24s Hitam Reaktif 25 kg', $cost->description);
        $this->assertEquals(25.5, $cost->quantity);
        $this->assertSame('kg', $cost->unit);
        $this->assertSame(2500000, $cost->amount);
        $this->assertNotNull($cost->receipt_path);
        $this->assertSame($this->admin->id, $cost->recorded_by);
        Storage::disk('local')->assertExists($cost->receipt_path);

        // Add second cost: CMT jahit
        $this->actingAs($this->admin)->post(route('admin.orders.costs.store', $this->order), [
            'category' => 'cmt_jahit',
            'description' => 'Upah Jahit Kaos',
            'quantity' => 100,
            'unit' => 'pcs',
            'amount' => 1000000,
            'spent_at' => '2026-09-15',
        ]);

        $this->order->refresh();
        $this->assertSame(3500000, $this->order->total_cost);
        // Gross Profit = 6.000.000 - 3.500.000 = 2.500.000
        $this->assertSame(2500000, $this->order->gross_profit);
        // Margin = (2.500.000 / 6.000.000) * 100 = 41.7%
        $this->assertEquals(41.7, $this->order->profit_margin);
        // HPP per unit = 3.500.000 / 100 = 35.000
        $this->assertSame(35000, $this->order->cost_per_unit);
        // Margin 41.7% >= 25% -> healthy
        $this->assertSame('healthy', $this->order->profit_status);
    }

    public function test_profit_status_accurately_reflects_margins(): void
    {
        // 1. Fair margin (15% - 24%)
        // Grand total = 6.000.000. Total cost = 4.800.000 -> Gross profit = 1.200.000 (20%)
        $cost1 = $this->order->costs()->create([
            'category' => 'kain',
            'description' => 'Bahan',
            'amount' => 4800000,
            'spent_at' => now(),
        ]);
        $this->order->refresh();
        $this->assertSame('fair', $this->order->profit_status);

        // 2. Thin margin (< 15%)
        // Add 600.000 -> Total cost = 5.400.000 -> Gross profit = 600.000 (10%)
        $cost2 = $this->order->costs()->create([
            'category' => 'makloon',
            'description' => 'Sablon',
            'amount' => 600000,
            'spent_at' => now(),
        ]);
        $this->order->refresh();
        $this->assertSame('thin', $this->order->profit_status);

        // 3. Loss (< 0%)
        // Add 1.000.000 -> Total cost = 6.400.000 -> Gross profit = -400.000
        $cost3 = $this->order->costs()->create([
            'category' => 'packing_operasional',
            'description' => 'Operasional',
            'amount' => 1000000,
            'spent_at' => now(),
        ]);
        $this->order->refresh();
        $this->assertSame('loss', $this->order->profit_status);
        $this->assertSame(-400000, $this->order->gross_profit);
    }

    public function test_can_view_receipt(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('struk.pdf', 100, 'application/pdf');
        $path = $file->store('order_costs/'.$this->order->id, 'local');

        $cost = $this->order->costs()->create([
            'category' => 'kain',
            'description' => 'Pembelian Kain',
            'amount' => 1500000,
            'spent_at' => now(),
            'receipt_path' => $path,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.costs.receipt', $cost));
        $response->assertOk();
    }

    public function test_destroy_cost_removes_receipt_and_recalculates(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('nota.jpg', 150, 'image/jpeg');
        $path = $file->store('order_costs/'.$this->order->id, 'local');

        $cost = $this->order->costs()->create([
            'category' => 'aksesoris',
            'description' => 'Kancing & Resleting',
            'amount' => 500000,
            'spent_at' => now(),
            'receipt_path' => $path,
        ]);

        Storage::disk('local')->assertExists($path);

        $response = $this->actingAs($this->admin)->delete(route('admin.orders.costs.destroy', [$this->order, $cost]));
        $response->assertRedirect(route('admin.orders.show', [$this->order, 'tab' => 'hpp']));

        $this->assertDatabaseMissing('order_costs', ['id' => $cost->id]);
        Storage::disk('local')->assertMissing($path);

        $this->order->refresh();
        $this->assertSame(0, $this->order->total_cost);
    }

    public function test_destroy_order_cascades_cost_receipt_deletion(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('struk_kain.jpg', 120, 'image/jpeg');
        $path = $file->store('order_costs/'.$this->order->id, 'local');

        $cost = $this->order->costs()->create([
            'category' => 'kain',
            'description' => 'Kain',
            'amount' => 1200000,
            'spent_at' => now(),
            'receipt_path' => $path,
        ]);

        Storage::disk('local')->assertExists($path);

        $this->actingAs($this->admin)->delete(route('admin.orders.destroy', $this->order));

        $this->assertDatabaseMissing('orders', ['id' => $this->order->id]);
        $this->assertDatabaseMissing('order_costs', ['id' => $cost->id]);
        Storage::disk('local')->assertMissing($path);
    }
}
