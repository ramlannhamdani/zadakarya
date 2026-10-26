<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_guest_cannot_access_loans(): void
    {
        $response = $this->get(route('admin.loans.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_loans_index_and_kpis(): void
    {
        $loan = Loan::create([
            'reference_no' => 'LOAN-202610-0001',
            'lender_name' => 'Bpk. H. Rahmat',
            'amount' => 15000000,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'purpose' => 'Talangan Kain Seragam Pemda',
            'status' => Loan::STATUS_UNPAID,
            'amount_paid' => 0,
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.loans.index'));
        $response->assertOk();
        $response->assertSee('Pinjaman & Dana Talangan');
        $response->assertSee('LOAN-202610-0001');
        $response->assertSee('Bpk. H. Rahmat');
        $response->assertSee('Rp 15.000.000');
    }

    public function test_admin_can_create_loan_without_order(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.loans.store'), [
            'lender_name' => 'Keluarga / Rekan',
            'amount' => 10000000,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'purpose' => 'Tambahan Kas Operasional',
            'notes' => 'Tanpa bunga, transfer via BCA',
        ]);

        $response->assertRedirect(route('admin.loans.index'));
        $this->assertDatabaseHas('loans', [
            'lender_name' => 'Keluarga / Rekan',
            'amount' => 10000000,
            'status' => Loan::STATUS_UNPAID,
            'amount_paid' => 0,
            'purpose' => 'Tambahan Kas Operasional',
        ]);
    }

    public function test_admin_can_create_loan_linked_to_order_with_receivables(): void
    {
        $customer = Customer::create(['name' => 'Dinas Pendidikan']);
        $order = Order::create([
            'order_number' => 'ZDK-0099-261026',
            'customer_id' => $customer->id,
            'name' => 'Seragam Batik Guru',
            'status' => 'active',
            'current_stage' => 2,
            'subtotal' => 25000000,
            'discount' => 0,
            'grand_total' => 25000000,
            'amount_paid' => 5000000,
            'payment_status' => 'partial',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.loans.store'), [
            'lender_name' => 'Bank BCA Modal Kerja',
            'amount' => 12000000,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(45)->toDateString(),
            'order_id' => $order->id,
            'purpose' => 'Talangan bahan batik selagi nunggu pelunasan Dinas',
        ]);

        $response->assertRedirect(route('admin.loans.index'));
        $loan = Loan::where('order_id', $order->id)->first();
        $this->assertNotNull($loan);
        $this->assertSame(12000000, $loan->amount);
        $this->assertSame('Bank BCA Modal Kerja', $loan->lender_name);

        // Cek bahwa di halaman show order muncul banner talangan
        $showResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $showResponse->assertOk();
        $showResponse->assertSee('Dana Talangan / Pinjaman Terkait Pesanan Ini');
        $showResponse->assertSee('Bank BCA Modal Kerja');
    }

    public function test_admin_can_record_repayment_and_status_changes_to_partial_then_paid(): void
    {
        $loan = Loan::create([
            'reference_no' => 'LOAN-202610-0002',
            'lender_name' => 'Pak Haji Somad',
            'amount' => 10000000,
            'loan_date' => now()->toDateString(),
            'status' => Loan::STATUS_UNPAID,
            'amount_paid' => 0,
            'recorded_by' => $this->admin->id,
        ]);

        // Cicilan pertama: 4 Juta
        $this->actingAs($this->admin)->post(route('admin.loans.payments.store', $loan), [
            'amount' => 4000000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'notes' => 'Cicilan pertama',
        ]);

        $loan->refresh();
        $this->assertSame(4000000, $loan->amount_paid);
        $this->assertSame(6000000, $loan->remaining_amount);
        $this->assertSame(Loan::STATUS_PARTIAL, $loan->status);

        // Cicilan kedua (pelunasan): 6 Juta
        $this->actingAs($this->admin)->post(route('admin.loans.payments.store', $loan), [
            'amount' => 6000000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'notes' => 'Pelunasan lunas penuh',
        ]);

        $loan->refresh();
        $this->assertSame(10000000, $loan->amount_paid);
        $this->assertSame(0, $loan->remaining_amount);
        $this->assertSame(Loan::STATUS_PAID, $loan->status);
    }

    public function test_admin_cannot_pay_more_than_remaining_debt(): void
    {
        $loan = Loan::create([
            'reference_no' => 'LOAN-202610-0003',
            'lender_name' => 'Ibu Siti',
            'amount' => 5000000,
            'loan_date' => now()->toDateString(),
            'status' => Loan::STATUS_UNPAID,
            'amount_paid' => 0,
            'recorded_by' => $this->admin->id,
        ]);

        // Coba bayar 6 Juta padahal sisa hutang 5 Juta
        $response = $this->actingAs($this->admin)->post(route('admin.loans.payments.store', $loan), [
            'amount' => 6000000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertSame(0, $loan->fresh()->amount_paid);
    }

    public function test_admin_can_update_and_delete_loan(): void
    {
        $loan = Loan::create([
            'reference_no' => 'LOAN-202610-0004',
            'lender_name' => 'Teman Lama',
            'amount' => 8000000,
            'loan_date' => now()->toDateString(),
            'status' => Loan::STATUS_UNPAID,
            'amount_paid' => 0,
            'recorded_by' => $this->admin->id,
        ]);

        // Update
        $this->actingAs($this->admin)->put(route('admin.loans.update', $loan), [
            'lender_name' => 'Teman Lama (Revisi)',
            'amount' => 9000000,
            'loan_date' => now()->toDateString(),
            'purpose' => 'Revisi keperluan',
        ]);

        $this->assertSame('Teman Lama (Revisi)', $loan->fresh()->lender_name);
        $this->assertSame(9000000, $loan->fresh()->amount);

        // Delete
        $response = $this->actingAs($this->admin)->delete(route('admin.loans.destroy', $loan));
        $response->assertRedirect(route('admin.loans.index'));
        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
    }
}
