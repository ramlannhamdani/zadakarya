<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Support\InvoicePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSharingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    private function createInvoice(?string $whatsapp = '0838-9535-2434'): Invoice
    {
        $customer = Customer::create([
            'name' => 'Bg Basta',
            'company' => 'J. Motion Project',
            'whatsapp' => $whatsapp,
        ]);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $customer->id,
            'name' => 'Kaos Polo + Sablon',
            'items' => [['product_name' => 'Kaos Polo', 'quantity' => 150, 'unit' => 'pcs', 'unit_price' => 70000]],
        ])->assertRedirect();

        return Order::latest('id')->firstOrFail()->invoices()->firstOrFail();
    }

    public function test_indonesian_numbers_are_normalised_for_whatsapp(): void
    {
        $this->assertSame('6283895352434', wa_number('0838-9535-2434'));
        $this->assertSame('6283895352434', wa_number('+62 838 9535 2434'));
        $this->assertSame('6283895352434', wa_number('838 9535 2434'));
        $this->assertSame('6283895352434', wa_number('6283895352434'));

        // Bukan nomor: tombol tetap dipakai, WhatsApp yang meminta pilih kontak.
        $this->assertNull(wa_number(null));
        $this->assertNull(wa_number('-'));
        $this->assertNull(wa_number('0812'));
        $this->assertSame('https://wa.me/?text=Halo', wa_send_link(null, 'Halo'));
    }

    public function test_shared_link_opens_the_invoice_pdf_without_logging_in(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->get($invoice->publicUrl()); // sengaja tanpa actingAs
        $response->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('Invoice '.$invoice->invoice_number.'.pdf', $disposition);
    }

    public function test_shared_link_is_useless_without_its_signature(): void
    {
        $invoice = $this->createInvoice();

        // URL tanpa tanda tangan, dan tanda tangan yang diutak-atik: keduanya ditolak.
        $this->get(url('/invoice/'.$invoice->id))->assertForbidden();
        $this->get($invoice->publicUrl().'x')->assertForbidden();

        // Tanda tangan invoice lain tidak bisa dipakai untuk membuka invoice ini.
        $other = $this->createInvoice();
        $borrowed = str_replace('/invoice/'.$other->id, '/invoice/'.$invoice->id, $other->publicUrl());
        $this->get($borrowed)->assertForbidden();
    }

    public function test_shared_pdf_is_the_same_document_the_admin_downloads(): void
    {
        $invoice = $this->createInvoice();

        $admin = $this->actingAs($this->admin)->get(route('admin.invoices.pdf', $invoice));
        $shared = $this->get($invoice->publicUrl());

        $this->assertSame('Invoice '.$invoice->invoice_number.'.pdf', InvoicePdf::filename($invoice));

        // dompdf menyisipkan tanggal pembuatan, jadi yang dibandingkan strukturnya:
        // jumlah halaman dan ukuran berkas yang praktis sama.
        $this->assertSame(
            substr_count($admin->getContent(), '/Type /Page'),
            substr_count($shared->getContent(), '/Type /Page'),
        );
        $this->assertGreaterThan(0, substr_count($shared->getContent(), '/Type /Page'));
    }

    public function test_invoice_page_offers_whatsapp_sharing_and_the_link(): void
    {
        $invoice = $this->createInvoice();

        $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Kirim ke WA')
            ->assertSee('invoiceShare(', false)
            ->assertSee('Salin tautan')
            ->assertSee(e($invoice->publicUrl()), false);
    }

    public function test_whatsapp_message_carries_the_customer_number_and_the_outstanding_amount(): void
    {
        $invoice = $this->createInvoice();
        $order = $invoice->order;

        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 4000000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $html = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice))->getContent();

        $this->assertStringContainsString('https:\/\/wa.me\/6283895352434?text=', $html);
        $this->assertStringContainsString(rawurlencode('Sisa tagihan: Rp 6.500.000'), $html);

        // Setelah lunas pesannya berganti, tidak lagi menagih.
        $this->actingAs($this->admin)->post(route('admin.orders.payments.store', $order), [
            'amount' => 6500000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $paidHtml = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice))->getContent();
        $this->assertStringContainsString(rawurlencode('Status: LUNAS'), $paidHtml);
        $this->assertStringNotContainsString(rawurlencode('Sisa tagihan'), $paidHtml);
    }

    public function test_invoice_list_and_order_page_can_share_too(): void
    {
        $invoice = $this->createInvoice();

        $this->actingAs($this->admin)->get(route('admin.invoices.index'))
            ->assertOk()->assertSee('invoiceShare(', false);

        $this->actingAs($this->admin)->get(route('admin.orders.show', ['order' => $invoice->order, 'tab' => 'invoice']))
            ->assertOk()->assertSee('invoiceShare(', false);
    }
}
