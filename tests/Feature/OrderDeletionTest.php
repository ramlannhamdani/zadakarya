<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->customer = Customer::create(['name' => 'Bg Basta', 'company' => 'J. Motion Project']);
    }

    private function createOrder(int $unitPrice = 9750000): Order
    {
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'name' => 'Kaos Polo + Sablon',
            'items' => [['product_name' => 'Kaos Polo', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => $unitPrice]],
        ]);

        return Order::latest('id')->first();
    }

    public function test_order_can_be_deleted_together_with_its_invoice_and_payments(): void
    {
        $order = $this->createOrder();

        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 9750000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $this->assertSame(9750000, (int) Payment::sum('amount'));
        $this->assertTrue($order->invoices()->exists());

        $this->actingAs($this->admin)->delete(route('admin.orders.destroy', $order))
            ->assertRedirect(route('admin.orders.index'));

        // Pesanan berikut seluruh turunannya hilang; pendapatan ikut menyesuaikan.
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('invoices', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('order_stages', ['order_id' => $order->id]);
        $this->assertSame(0, (int) Payment::sum('amount'));
    }

    public function test_deleting_an_order_removes_its_files(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();

        $this->actingAs($this->admin)->post(route('admin.orders.photos.store', $order), [
            'photos' => [UploadedFile::fake()->image('produksi.jpg', 900, 600)],
            'stage_number' => 4,
            'visibility' => 'internal',
        ]);

        $photo = $order->productionPhotos()->firstOrFail();
        Storage::disk('local')->assertExists($photo->image_path);

        $this->actingAs($this->admin)->delete(route('admin.orders.destroy', $order));

        Storage::disk('local')->assertMissing($photo->image_path);
        Storage::disk('local')->assertMissing($photo->thumb_path);
    }

    public function test_dashboard_revenue_drops_after_deleting_an_order(): void
    {
        $first = $this->createOrder(5000000);
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $first), [
            'amount' => 5000000, 'payment_date' => now()->toDateString(), 'method' => 'cash',
        ]);

        $second = $this->createOrder(3000000);
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $second), [
            'amount' => 3000000, 'payment_date' => now()->toDateString(), 'method' => 'cash',
        ]);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Rp 8.000.000');

        $this->actingAs($this->admin)->delete(route('admin.orders.destroy', $second));

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Rp 5.000.000')->assertDontSee('Rp 8.000.000');
    }

    public function test_each_invoice_shows_only_its_own_payments(): void
    {
        $order = $this->createOrder(10500000);
        $first = $order->invoices()->firstOrFail();

        // Dua invoice tambahan pada pesanan yang sama, seperti penagihan bertahap.
        foreach ([10500000, 4500000] as $amount) {
            $this->actingAs($this->admin)->post(route('admin.invoices.store'), [
                'order_id' => $order->id,
                'date' => now()->toDateString(),
                'items' => [['description' => 'Kaos Polo', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => $amount]],
            ]);
        }

        $invoices = $order->invoices()->orderBy('id')->get();
        $this->assertCount(3, $invoices);

        // Tiap invoice dibayar penuh, masing-masing ditautkan ke invoicenya.
        foreach ($invoices as $invoice) {
            $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
                'amount' => $invoice->grand_total,
                'payment_date' => now()->toDateString(),
                'method' => 'transfer',
                'invoice_id' => $invoice->id,
            ]);
        }

        $this->assertSame(25500000, (int) $order->fresh()->amount_paid);

        // Meski total pesanan 25,5 juta, tiap invoice hanya menampilkan bagiannya.
        foreach ($invoices as $invoice) {
            $invoice->load(['order.customer', 'order.payments', 'order.invoices', 'items']);
            $html = view('admin.invoices.pdf', compact('invoice'))->render();

            $this->assertStringContainsString('Terbayar</td><td class="c">:</td><td>'.rupiah($invoice->grand_total), $html);
            $this->assertStringNotContainsString('Terbayar</td><td class="c">:</td><td>Rp 25.500.000', $html);
        }
    }

    public function test_existing_payment_can_be_linked_to_an_invoice_afterwards(): void
    {
        $order = $this->createOrder(5000000);
        $invoice = $order->invoices()->firstOrFail();

        // Pembayaran lama yang dicatat tanpa memilih invoice.
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 5000000, 'payment_date' => now()->toDateString(), 'method' => 'transfer',
        ]);

        $payment = $order->payments()->firstOrFail();
        $this->assertNull($payment->invoice_id);

        $this->actingAs($this->admin)
            ->patch(route('admin.payments.link', $payment), ['invoice_id' => $invoice->id])
            ->assertRedirect();

        $this->assertSame($invoice->id, $payment->fresh()->invoice_id);

        // Invoice pesanan lain tidak boleh dipilih.
        $otherInvoice = $this->createOrder(1000000)->invoices()->firstOrFail();
        $this->actingAs($this->admin)
            ->patch(route('admin.payments.link', $payment), ['invoice_id' => $otherInvoice->id])
            ->assertSessionHasErrors('invoice_id');
    }

    public function test_unpaid_invoice_in_a_multi_invoice_order_is_not_marked_paid(): void
    {
        $order = $this->createOrder(5000000);
        $paidInvoice = $order->invoices()->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.invoices.store'), [
            'order_id' => $order->id,
            'date' => now()->toDateString(),
            'items' => [['description' => 'Batch kedua', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 2000000]],
        ]);

        $unpaidInvoice = $order->invoices()->orderByDesc('id')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 5000000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'invoice_id' => $paidInvoice->id,
        ]);

        foreach ([[$paidInvoice, 'LUNAS'], [$unpaidInvoice, 'BELUM LUNAS']] as [$invoice, $expected]) {
            $invoice->load(['order.customer', 'order.payments', 'order.invoices', 'items']);
            $html = view('admin.invoices.pdf', compact('invoice'))->render();
            $this->assertStringContainsString('>'.$expected.'<', $html);
        }
    }

    public function test_order_page_warns_when_invoice_total_differs_from_order_total(): void
    {
        $order = $this->createOrder(9750000);

        // Selama invoice sama dengan item pesanan, tidak ada peringatan.
        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
            ->assertOk()->assertDontSee('tidak sama dengan Grand Total');

        // Invoice tambahan membuat total tagihan melampaui nilai item pesanan.
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), [
            'order_id' => $order->id,
            'date' => now()->toDateString(),
            'items' => [['description' => 'Batch kedua', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 4500000]],
        ]);

        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('tidak sama dengan Grand Total')
            ->assertSee('Rp 14.250.000');
    }

    public function test_payments_are_never_shared_between_orders_of_the_same_customer(): void
    {
        $first = $this->createOrder(9750000);
        $second = $this->createOrder(9750000);

        // Pesanan pertama dilunasi; pesanan kedua tidak disentuh sama sekali.
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $first), [
            'amount' => 9750000, 'payment_date' => now()->toDateString(), 'method' => 'transfer',
        ]);

        $first->refresh();
        $second->refresh();

        $this->assertSame('paid', $first->payment_status);
        $this->assertSame(9750000, $first->amount_paid);
        $this->assertSame(0, $first->remaining);

        $this->assertSame('unpaid', $second->payment_status);
        $this->assertSame(0, $second->amount_paid);
        $this->assertSame(9750000, $second->remaining);

        // Pelunasan pesanan kedua juga tidak mengubah pesanan pertama.
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $second), [
            'amount' => 4000000, 'payment_date' => now()->toDateString(), 'method' => 'cash',
        ]);

        $this->assertSame('paid', $first->fresh()->payment_status);
        $this->assertSame(9750000, $first->fresh()->amount_paid);
        $this->assertSame('partial', $second->fresh()->payment_status);
        $this->assertSame(4000000, $second->fresh()->amount_paid);
    }
}
