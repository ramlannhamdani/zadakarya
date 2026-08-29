<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->customer = Customer::create(['name' => 'Budi', 'company' => 'PT ABC']);
    }

    private function createOrder(array $overrides = []): Order
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), array_merge([
            'customer_id' => $this->customer->id,
            'name' => 'Seragam Polo 2026',
            'items' => [
                ['product_name' => 'Polo Shirt', 'quantity' => 100, 'unit' => 'pcs', 'unit_price' => 85000],
                ['product_name' => 'Celana', 'quantity' => 20, 'unit' => 'pcs', 'unit_price' => 75000],
            ],
        ], $overrides))->assertRedirect();

        return Order::latest('id')->first();
    }

    public function test_order_numbers_are_sequential_with_time_suffix(): void
    {
        $first = $this->createOrder();
        $second = $this->createOrder();

        // Format: ZDK-XXXX-HHMMTT — inti berurutan, akhiran tanggal-bulan-tahun pembuatan.
        $this->assertMatchesRegularExpression('/^ZDK-0001-\d{6}$/', $first->order_number);
        $this->assertMatchesRegularExpression('/^ZDK-0002-\d{6}$/', $second->order_number);
        $this->assertNotSame($first->order_number, $second->order_number);
    }

    public function test_order_creates_seven_stages_with_first_in_progress(): void
    {
        $order = $this->createOrder();

        $this->assertCount(7, $order->stages);
        $this->assertSame('in_progress', $order->stages->firstWhere('stage_number', 1)->status);
        $this->assertSame('pending', $order->stages->firstWhere('stage_number', 2)->status);
        $this->assertSame(1, $order->current_stage);
        $this->assertSame(100 * 85000 + 20 * 75000, $order->grand_total);
    }

    public function test_completing_a_stage_auto_starts_the_next(): void
    {
        $order = $this->createOrder();
        $stage1 = $order->stages()->where('stage_number', 1)->first();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.stages.complete', [$order, $stage1]))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->stages()->where('stage_number', 1)->first()->status);
        $this->assertSame('in_progress', $order->stages()->where('stage_number', 2)->first()->status);
        $this->assertSame(2, $order->current_stage);
    }

    public function test_completing_final_stage_marks_order_completed(): void
    {
        $order = $this->createOrder();

        foreach (range(1, 7) as $number) {
            $stage = $order->stages()->where('stage_number', $number)->first();
            $this->actingAs($this->admin)->post(route('admin.orders.stages.complete', [$order, $stage]));
        }

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertTrue($order->stages->every(fn ($s) => $s->status === 'completed'));
    }

    public function test_payment_status_transitions_unpaid_partial_paid(): void
    {
        $order = $this->createOrder();
        $this->assertSame('unpaid', $order->payment_status);

        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 5000000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $order->refresh();
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame(5000000, $order->amount_paid);
        $this->assertSame($order->grand_total - 5000000, $order->remaining);

        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => $order->remaining,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(0, $order->remaining);
    }

    public function test_order_creation_auto_creates_invoice_and_records_dp(): void
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Pesanan DP',
            'dp_amount' => 3000000,
            'create_invoice' => 1,
            'record_dp' => 1,
            'dp_date' => now()->toDateString(),
            'dp_method' => 'cash',
            'items' => [['product_name' => 'Polo', 'quantity' => 100, 'unit' => 'pcs', 'unit_price' => 85000]],
        ])->assertRedirect();

        $order = Order::latest('id')->first();
        $invoice = $order->invoices()->first();

        $this->assertNotNull($invoice);
        $this->assertSame('INV-0001', $invoice->invoice_number);
        $this->assertSame(1, $invoice->items()->count());
        $this->assertSame(8500000, $invoice->grand_total);
        $this->assertSame(8500000, $order->grand_total);   // DP tidak mengurangi grand total
        $this->assertSame(3000000, $order->amount_paid);    // ...tapi tercatat sebagai pembayaran
        $this->assertSame(5500000, $order->remaining);
        $this->assertSame('partial', $order->payment_status);
    }

    public function test_auto_invoice_can_be_skipped(): void
    {
        $this->createOrder(['create_invoice' => 0]);

        $this->assertSame(0, \App\Models\Invoice::count());
    }

    public function test_invoice_numbers_are_sequential_and_independent_from_orders(): void    {
        $order = $this->createOrder();

        $payload = [
            'order_id' => $order->id,
            'date' => now()->toDateString(),
            'items' => [['description' => 'Polo Shirt', 'quantity' => 100, 'unit' => 'pcs', 'unit_price' => 85000]],
        ];

        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $payload)->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $payload)->assertRedirect();

        $numbers = $order->invoices()->orderBy('id')->pluck('invoice_number');
        // INV-0001 dibuat otomatis saat pesanan dibuat; dua invoice manual menyusul.
        $this->assertSame(['INV-0001', 'INV-0002', 'INV-0003'], $numbers->all());
        $this->assertSame(8500000, \App\Models\Invoice::where('invoice_number', 'INV-0002')->first()->grand_total);
    }

    public function test_invoice_pdf_downloads(): void
    {
        $order = $this->createOrder();
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), [
            'order_id' => $order->id,
            'date' => now()->toDateString(),
            'discount' => 500000,
            'items' => [['description' => 'Polo Shirt', 'quantity' => 100, 'unit' => 'pcs', 'unit_price' => 85000]],
        ]);

        $invoice = $order->invoices()->orderByDesc('id')->first(); // invoice manual (dengan diskon)
        $this->assertSame(8000000, $invoice->grand_total);

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.pdf', $invoice));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
