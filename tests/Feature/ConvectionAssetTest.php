<?php

namespace Tests\Feature;

use App\Models\ConvectionAsset;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConvectionAssetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_guest_cannot_access_assets_or_financial_reports(): void
    {
        $this->get(route('admin.assets.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.finance.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_assets_index(): void
    {
        ConvectionAsset::create([
            'code' => 'AST-001',
            'name' => 'Mesin Jahit Juki DDL-8100e',
            'category' => ConvectionAsset::CATEGORY_MACHINERY,
            'purchase_date' => '2025-01-15',
            'quantity' => 4,
            'unit' => 'unit',
            'purchase_price' => 4200000,
            'current_value' => 3800000,
            'condition' => ConvectionAsset::CONDITION_GOOD,
            'location' => 'Lantai 1 Workshop',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.assets.index'));

        $response->assertOk();
        $response->assertSee('Aset & Valuasi Konveksi');
        $response->assertSee('Mesin Jahit Juki DDL-8100e');
        $response->assertSee('AST-001');
    }

    public function test_admin_can_create_asset_with_auto_generated_code(): void
    {
        $payload = [
            'name' => 'Meja Sablon Rel 12 Palet',
            'category' => ConvectionAsset::CATEGORY_PRINTING_EMBROIDERY,
            'purchase_date' => '2025-05-10',
            'quantity' => 1,
            'unit' => 'set',
            'purchase_price' => 14000000,
            'current_value' => 12500000,
            'condition' => ConvectionAsset::CONDITION_GOOD,
            'location' => 'Area Sablon',
            'notes' => 'Presisi tinggi rel besi hollow',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.assets.store'), $payload);

        $response->assertRedirect(route('admin.assets.index'));
        $this->assertDatabaseHas('convection_assets', [
            'name' => 'Meja Sablon Rel 12 Palet',
            'category' => ConvectionAsset::CATEGORY_PRINTING_EMBROIDERY,
            'purchase_price' => 14000000,
            'current_value' => 12500000,
            'quantity' => 1,
            'unit' => 'set',
        ]);

        $asset = ConvectionAsset::where('name', 'Meja Sablon Rel 12 Palet')->first();
        $this->assertNotNull($asset);
        $this->assertMatchesRegularExpression('/^AST-[0-9]{3,}$/', $asset->code);
    }

    public function test_admin_can_update_asset(): void
    {
        $asset = ConvectionAsset::create([
            'code' => 'AST-010',
            'name' => 'Setrika Uap Boiler 25 Liter',
            'category' => ConvectionAsset::CATEGORY_FINISHING_STEAMER,
            'quantity' => 1,
            'unit' => 'set',
            'purchase_price' => 3800000,
            'current_value' => 3200000,
            'condition' => ConvectionAsset::CONDITION_GOOD,
            'location' => 'Area Finishing',
            'recorded_by' => $this->admin->id,
        ]);

        $updatePayload = [
            'name' => 'Setrika Uap Boiler 25L Nagamoto',
            'category' => ConvectionAsset::CATEGORY_FINISHING_STEAMER,
            'purchase_date' => '2025-03-01',
            'quantity' => 2,
            'unit' => 'set',
            'purchase_price' => 7600000,
            'current_value' => 6400000,
            'condition' => ConvectionAsset::CONDITION_GOOD,
            'location' => 'Area Finishing & Packing',
            'notes' => 'Tambahan 1 kepala setrika',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.assets.update', $asset), $updatePayload);

        $response->assertRedirect(route('admin.assets.index'));
        $this->assertDatabaseHas('convection_assets', [
            'id' => $asset->id,
            'name' => 'Setrika Uap Boiler 25L Nagamoto',
            'quantity' => 2,
            'purchase_price' => 7600000,
            'current_value' => 6400000,
            'condition' => ConvectionAsset::CONDITION_GOOD,
        ]);
    }

    public function test_admin_can_delete_asset(): void
    {
        $asset = ConvectionAsset::create([
            'code' => 'AST-099',
            'name' => 'Mesin Obras Rusak',
            'category' => ConvectionAsset::CATEGORY_MACHINERY,
            'quantity' => 1,
            'purchase_price' => 2000000,
            'current_value' => 500000,
            'condition' => ConvectionAsset::CONDITION_DAMAGED,
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.assets.destroy', $asset));

        $response->assertRedirect(route('admin.assets.index'));
        $this->assertDatabaseMissing('convection_assets', ['id' => $asset->id]);
    }

    public function test_admin_can_view_financial_reports_and_balance_sheet(): void
    {
        // Setup initial assets
        ConvectionAsset::create([
            'code' => 'AST-001',
            'name' => 'Mesin Jahit Juki',
            'category' => ConvectionAsset::CATEGORY_MACHINERY,
            'quantity' => 2,
            'purchase_price' => 8000000,
            'current_value' => 7000000,
            'condition' => ConvectionAsset::CONDITION_GOOD,
            'recorded_by' => $this->admin->id,
        ]);

        // Setup active order
        $customer = Customer::create([
            'name' => 'PT Mitra Finansial Utama',
            'phone' => '081234567890',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_id' => $customer->id,
            'name' => 'Kaos Polo 100 pcs',
            'total_quantity' => 100,
            'subtotal' => 10000000,
            'grand_total' => 10000000,
            'status' => 'active',
            'payment_status' => 'partial',
            'created_by' => $this->admin->id,
        ]);

        // Setup payment and cost
        Payment::create([
            'order_id' => $order->id,
            'amount' => 5000000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'type' => 'down_payment',
        ]);

        OrderCost::create([
            'order_id' => $order->id,
            'category' => 'fabric',
            'description' => 'Kain Lacoste Pique',
            'quantity' => 25,
            'unit' => 'kg',
            'unit_price' => 120000,
            'amount' => 3000000,
            'spent_at' => now()->toDateString(),
            'recorded_by' => $this->admin->id,
        ]);

        // Setup loan
        Loan::create([
            'reference_no' => 'LOAN-TEST-01',
            'lender_name' => 'Mitra Finansial',
            'amount' => 4000000,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'unpaid',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.index'));

        $response->assertOk();
        $response->assertSee('Neraca');
        $response->assertSee('Laba Rugi');
        $response->assertSee('Valuasi Bersih Konveksi');
        $response->assertSee('AKTIVA (ASET)');
        $response->assertSee('PASIVA');
        $response->assertSee('Mesin Jahit');
    }

    public function test_google_sheet_service_formats_and_includes_assets(): void
    {
        $asset = ConvectionAsset::create([
            'code' => 'AST-005',
            'name' => 'Mesin Potong KM 8 Inch',
            'category' => ConvectionAsset::CATEGORY_CUTTING_FINISHING,
            'quantity' => 2,
            'unit' => 'unit',
            'purchase_price' => 6000000,
            'current_value' => 5200000,
            'condition' => ConvectionAsset::CONDITION_GOOD,
            'location' => 'Meja Pola',
            'recorded_by' => $this->admin->id,
        ]);

        $service = app(GoogleSheetService::class);
        $formatted = $service->formatAssetData($asset);

        $this->assertEquals('AST-005', $formatted['id']);
        $this->assertEquals('Mesin Potong KM 8 Inch', $formatted['name']);
        $this->assertEquals(2, $formatted['qty']);
        $this->assertEquals(6000000, $formatted['purchase_price']);
        $this->assertEquals(5200000, $formatted['current_value']);
        $this->assertEquals('Baik / Prima', $formatted['condition']);
    }
}
