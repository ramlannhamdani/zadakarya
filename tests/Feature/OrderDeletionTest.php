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
