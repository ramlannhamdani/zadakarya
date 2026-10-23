<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_guest_cannot_access_expenses_page(): void
    {
        $response = $this->get(route('admin.expenses.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_expenses_index(): void
    {
        OperationalExpense::create([
            'reference_no' => 'EXP-202609-0001',
            'category' => 'listrik_air',
            'title' => 'Token Listrik Workshop September',
            'amount' => 750000,
            'expense_date' => now(),
            'payment_method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.expenses.index'));

        $response->assertOk();
        $response->assertSee('Beban Operasional');
        $response->assertSee('Token Listrik Workshop September');
        $response->assertSee('EXP-202609-0001');
        $response->assertSee('Listrik, Air & Utilitas');
        $response->assertSee('Rp 750.000');
    }

    public function test_admin_can_create_operational_expense(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'category' => 'sewa_gedung',
            'title' => 'Sewa Gedung Workshop Semester 2',
            'amount' => '15.000.000',
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'notes' => 'Transfer ke rekening pemilik ruko BCA',
        ]);

        $response->assertRedirect(route('admin.expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('operational_expenses', [
            'category' => 'sewa_gedung',
            'title' => 'Sewa Gedung Workshop Semester 2',
            'amount' => 15000000,
            'payment_method' => 'transfer',
        ]);

        $expense = OperationalExpense::first();
        $this->assertNotNull($expense);
        $this->assertMatchesRegularExpression('/^EXP-\d{6}-\d{4}$/', $expense->reference_no);
    }

    public function test_admin_can_upload_receipt_and_view_it(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('struk_listrik.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'category' => 'listrik_air',
            'title' => 'Bayar Listrik PLN',
            'amount' => 500000,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'receipt' => $file,
        ]);

        $response->assertRedirect(route('admin.expenses.index'));

        $expense = OperationalExpense::first();
        $this->assertNotNull($expense->receipt_path);
        Storage::disk('local')->assertExists($expense->receipt_path);

        // Akses route download / view receipt
        $receiptResponse = $this->actingAs($this->admin)->get(route('admin.expenses.receipt', $expense));
        $receiptResponse->assertOk();
    }

    public function test_admin_can_update_operational_expense(): void
    {
        $expense = OperationalExpense::create([
            'reference_no' => 'EXP-202609-0001',
            'category' => 'operasional_kantor',
            'title' => 'Beli ATK',
            'amount' => 150000,
            'expense_date' => now(),
            'payment_method' => 'cash',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.expenses.update', $expense), [
            'category' => 'operasional_kantor',
            'title' => 'Beli ATK & Kertas Nota',
            'amount' => '250.000',
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'notes' => 'Ditambah kertas F4 2 rim',
        ]);

        $response->assertRedirect(route('admin.expenses.index'));
        $this->assertDatabaseHas('operational_expenses', [
            'id' => $expense->id,
            'title' => 'Beli ATK & Kertas Nota',
            'amount' => 250000,
            'notes' => 'Ditambah kertas F4 2 rim',
        ]);
    }

    public function test_admin_can_delete_operational_expense(): void
    {
        $expense = OperationalExpense::create([
            'reference_no' => 'EXP-202609-0001',
            'category' => 'internet_pulsa',
            'title' => 'Tagihan Indihome',
            'amount' => 450000,
            'expense_date' => now(),
            'payment_method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.expenses.destroy', $expense));

        $response->assertRedirect(route('admin.expenses.index'));
        $this->assertDatabaseMissing('operational_expenses', ['id' => $expense->id]);
    }

    public function test_expense_auto_sync_triggers_webhook(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_test_123/exec';
        Setting::set('google_sheet_webhook_url', $webhookUrl);
        Setting::set('google_sheet_auto_sync', '1');

        Http::fake([
            $webhookUrl => Http::response(['success' => true], 200),
        ]);

        $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'category' => 'listrik_air',
            'title' => 'Token Listrik 500rb',
            'amount' => 500000,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['type'])
                && $data['type'] === 'expense'
                && ($data['expense']['amount'] ?? 0) === 500000
                && str_contains($data['expense']['category'] ?? '', 'Beban Operasional');
        });
    }

    public function test_dashboard_displays_operational_expenses_and_net_profit(): void
    {
        $customer = Customer::create(['name' => 'PT Maju Bersama', 'phone' => '08123456789']);

        // Order nilai 10.000.000
        $order = Order::create([
            'order_number' => 'ZK-2609-010',
            'customer_id' => $customer->id,
            'name' => 'Jaket Angkatan 100 Pcs',
            'status' => 'active',
            'current_stage' => 1,
            'subtotal' => 10000000,
            'discount' => 0,
            'grand_total' => 10000000,
            'amount_paid' => 10000000,
            'payment_status' => 'paid',
        ]);

        // HPP 4.000.000 -> Laba Kotor = 6.000.000
        OrderCost::create([
            'order_id' => $order->id,
            'category' => 'kain',
            'description' => 'Kain Fleece',
            'quantity' => 50,
            'unit' => 'kg',
            'unit_price' => 80000,
            'amount' => 4000000,
            'spent_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        // Beban Operasional 1.500.000 -> Laba Bersih = 4.500.000
        OperationalExpense::create([
            'reference_no' => 'EXP-202609-0001',
            'category' => 'listrik_air',
            'title' => 'Listrik & Air Workshop',
            'amount' => 1500000,
            'expense_date' => now(),
            'payment_method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Laba Perusahaan & Beban Operasional');
        $response->assertSee('Total HPP Produksi');
        $response->assertSee('Rp 4.000.000');
        $response->assertSee('Laba Kotor Pesanan');
        $response->assertSee('Rp 6.000.000');
        $response->assertSee('Ops. Konveksi');
        $response->assertSee('Rp 1.500.000');
        $response->assertSee('Laba Bersih');
        $response->assertSee('Rp 4.500.000');
    }

    public function test_convection_and_personal_expenses_tracking_and_filtering(): void
    {
        // 1. Catat Ops. Konveksi (Beban Bisnis)
        $convection = OperationalExpense::create([
            'reference_no' => 'EXP-202610-0001',
            'expense_type' => 'convection',
            'category' => 'listrik_air',
            'title' => 'Listrik Mesin Konveksi',
            'amount' => 2000000,
            'expense_date' => now(),
            'payment_method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        // 2. Catat Ops. Pribadi (Prive Owner)
        $personal = OperationalExpense::create([
            'reference_no' => 'EXP-202610-0002',
            'expense_type' => 'personal',
            'category' => 'prive_owner',
            'title' => 'Prive Kebutuhan Owner',
            'amount' => 1000000,
            'expense_date' => now(),
            'payment_method' => 'cash',
            'recorded_by' => $this->admin->id,
        ]);

        $this->assertSame('Ops. Konveksi', $convection->type_label);
        $this->assertTrue($convection->isConvection());
        $this->assertFalse($convection->isPersonal());

        $this->assertSame('Ops. Pribadi', $personal->type_label);
        $this->assertTrue($personal->isPersonal());
        $this->assertFalse($personal->isConvection());

        // 3. Test Dashboard menampilkan kedua metrik
        $dashResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Ops. Konveksi');
        $dashResponse->assertSee('Rp 2.000.000');
        $dashResponse->assertSee('Ops. Pribadi');
        $dashResponse->assertSee('Rp 1.000.000');

        // 4. Test Filter di Halaman Beban Operasional
        // Filter convection: hanya melihat Ops. Konveksi
        $convFilterResponse = $this->actingAs($this->admin)->get(route('admin.expenses.index', ['expense_type' => 'convection']));
        $convFilterResponse->assertOk();
        $convFilterResponse->assertSee('Listrik Mesin Konveksi');
        $convFilterResponse->assertDontSee('Prive Kebutuhan Owner');

        // Filter personal: hanya melihat Ops. Pribadi
        $persFilterResponse = $this->actingAs($this->admin)->get(route('admin.expenses.index', ['expense_type' => 'personal']));
        $persFilterResponse->assertOk();
        $persFilterResponse->assertSee('Prive Kebutuhan Owner');
        $persFilterResponse->assertDontSee('Listrik Mesin Konveksi');

        // 5. Test Google Sheet Format
        $sheetService = new \App\Services\GoogleSheetService();
        $formattedConv = $sheetService->formatExpenseData($convection);
        $this->assertStringContainsString('[Ops. Konveksi]', $formattedConv['desc']);

        $formattedPers = $sheetService->formatExpenseData($personal);
        $this->assertStringContainsString('[Ops. Pribadi]', $formattedPers['desc']);
    }
}
