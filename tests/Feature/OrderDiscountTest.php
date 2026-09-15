<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->customer = Customer::create(['name' => 'Budi Santoso', 'company' => 'PT Makmur']);
    }

    public function test_order_creation_with_discount_calculates_subtotal_and_grand_total(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Seragam Kantor Drill',
            'discount' => 250000,
            'items' => [
                ['product_name' => 'Kemeja Drill', 'quantity' => 50, 'unit' => 'pcs', 'unit_price' => 100000], // 5.000.000
                ['product_name' => 'Celana Drill', 'quantity' => 50, 'unit' => 'pcs', 'unit_price' => 80000],  // 4.000.000
            ],
        ]);

        $response->assertRedirect();

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(9000000, $order->subtotal);
        $this->assertSame(250000, $order->discount);
        $this->assertSame(8750000, $order->grand_total);
        $this->assertSame(8750000, $order->remaining);
        $this->assertSame('unpaid', $order->payment_status);
    }

    public function test_auto_created_invoice_inherits_order_discount(): void
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Jaket Angkatan',
            'discount' => 500000,
            'create_invoice' => 1,
            'items' => [
                ['product_name' => 'Jaket Parka', 'quantity' => 100, 'unit' => 'pcs', 'unit_price' => 150000], // 15.000.000
            ],
        ])->assertRedirect();

        $order = Order::latest('id')->firstOrFail();
        $invoice = $order->invoices()->firstOrFail();

        $this->assertSame(15000000, $order->subtotal);
        $this->assertSame(500000, $order->discount);
        $this->assertSame(14500000, $order->grand_total);

        // Invoice otomatis mewarisi diskon yang sama
        $this->assertSame(15000000, $invoice->subtotal);
        $this->assertSame(500000, $invoice->discount);
        $this->assertSame(14500000, $invoice->grand_total);

        // Halaman pesanan tidak memunculkan peringatan ketidaksesuaian total invoice
        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('tidak sama dengan Grand Total')
            ->assertSee('Subtotal')
            ->assertSee('- Rp 500.000')
            ->assertSee('Rp 14.500.000');
    }

    public function test_discount_influences_payment_status_and_remaining(): void
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Kaos Event',
            'discount' => 200000,
            'items' => [
                ['product_name' => 'Kaos Combed 30s', 'quantity' => 100, 'unit' => 'pcs', 'unit_price' => 50000], // 5.000.000 -> grand_total 4.800.000
            ],
        ]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(4800000, $order->grand_total);

        // Bayar 2.000.000 -> status DP / partial
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 2000000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $order->refresh();
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame(2800000, $order->remaining);

        // Pelunasan 2.800.000 -> status lunas
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 2800000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(0, $order->remaining);
    }

    public function test_order_update_recalculates_discount_and_totals(): void
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Polo Shirt Bordir',
            'discount' => 100000,
            'items' => [
                ['product_name' => 'Polo Shirt', 'quantity' => 20, 'unit' => 'pcs', 'unit_price' => 80000], // 1.600.000 - 100.000 = 1.500.000
            ],
        ]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(1500000, $order->grand_total);

        // Update diskon dinaikkan menjadi 300.000
        $this->actingAs($this->admin)->put(route('admin.orders.update', $order), [
            'customer_id' => $this->customer->id,
            'name' => 'Polo Shirt Bordir Revisi',
            'discount' => 300000,
            'items' => [
                ['product_name' => 'Polo Shirt', 'quantity' => 20, 'unit' => 'pcs', 'unit_price' => 80000],
            ],
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame(1600000, $order->subtotal);
        $this->assertSame(300000, $order->discount);
        $this->assertSame(1300000, $order->grand_total);
        $this->assertSame(1300000, $order->remaining);
    }

    public function test_manual_invoice_creation_prefills_order_discount(): void
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Pesanan Manual Invoice',
            'discount' => 150000,
            'create_invoice' => 0,
            'items' => [
                ['product_name' => 'Kemeja Flanel', 'quantity' => 10, 'unit' => 'pcs', 'unit_price' => 120000],
            ],
        ]);

        $order = Order::latest('id')->firstOrFail();

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.create', ['order' => $order->id]));
        $response->assertOk();
        $response->assertSee('discount: 150000', false);
    }

    public function test_invoice_pdf_shows_discount_and_subtotal(): void
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Pesanan Cetak PDF',
            'discount' => 200000,
            'create_invoice' => 1,
            'items' => [
                ['product_name' => 'Rompi Safety', 'quantity' => 50, 'unit' => 'pcs', 'unit_price' => 100000],
            ],
        ]);

        $order = Order::latest('id')->firstOrFail();
        $invoice = $order->invoices()->firstOrFail();

        $html = view('admin.invoices.pdf', ['invoice' => $invoice->load(['order.customer', 'order.payments', 'items'])])->render();
        $this->assertStringContainsString('Subtotal :', $html);
        $this->assertStringContainsString('Rp 5.000.000', $html);
        $this->assertStringContainsString('Diskon :', $html);
        $this->assertStringContainsString('- Rp 200.000', $html);
        $this->assertStringContainsString('TOTAL :', $html);
        $this->assertStringContainsString('Rp 4.800.000', $html);
    }
}
