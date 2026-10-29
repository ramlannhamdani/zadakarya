<?php

namespace Tests\Feature;

use App\Models\ConvectionAsset;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFinancialOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(12, 0));
        $this->admin = User::factory()->create();
        $this->customer = Customer::create(['name' => 'Pelanggan Uji']);
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'UJI-'.(Order::count() + 1), 'customer_id' => $this->customer->id,
            'name' => 'Seragam Uji', 'status' => 'active', 'current_stage' => 1,
            'subtotal' => 5000000, 'grand_total' => 5000000, 'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ], $attributes));
    }

    public function test_dp_position_is_identical_in_web_and_sheet_and_excludes_settlement(): void
    {
        $order = $this->order();
        $order->payments()->create(['amount' => 2000000, 'type' => Payment::TYPE_DP, 'payment_date' => today(), 'method' => 'cash']);
        $order->payments()->create(['amount' => 3000000, 'type' => Payment::TYPE_SETTLEMENT, 'payment_date' => today(), 'method' => 'cash']);
        $order->costs()->create(['category' => 'kain', 'description' => 'Kain', 'amount' => 3000000, 'spent_at' => today()]);
        $order->refreshPaymentStatus();
        $order = $order->fresh(['payments', 'costs']);

        $this->assertSame(-1000000, $order->dp_vs_hpp);
        $this->assertSame(2000000, $order->net_cash_flow);
        $data = app(GoogleSheetService::class)->formatOrderData($order);
        $this->assertSame($order->dp_vs_hpp, $data['dp_vs_hpp']);
        $this->assertSame(0, $data['cash_advance']);
        $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->assertSee('-Rp 1.000.000')->assertSee('DP Kurang');
        $this->get(route('admin.orders.show', ['order' => $order, 'tab' => 'hpp']))->assertOk()->assertSee('-Rp 1.000.000');

        // Kolom ringkasan pembayaran tanpa bukti jenis tidak boleh dianggap DP.
        $legacy = $this->order(['amount_paid' => 1500000]);
        $this->assertSame(0, $legacy->dp_paid);
    }

    public function test_positive_zero_and_negative_dp_positions_have_the_correct_badges(): void
    {
        foreach ([875000 => '+Rp 1.125.000', 2000000 => 'Rp 0', 3000000 => '-Rp 1.000.000'] as $hpp => $label) {
            $order = $this->order(['amount_paid' => 2000000, 'payment_status' => 'partial']);
            $order->payments()->create(['amount' => 2000000, 'type' => Payment::TYPE_DP, 'payment_date' => today(), 'method' => 'cash']);
            $order->costs()->create(['category' => 'kain', 'description' => 'Kain', 'amount' => $hpp, 'spent_at' => today()]);
            $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->assertSee($label);
            $this->assertSame(2000000 - $hpp, app(GoogleSheetService::class)->formatOrderData($order->fresh())['dp_vs_hpp']);
        }
    }

    public function test_totals_cover_all_orders_and_critical_receivables_do_not_overlap_running(): void
    {
        for ($i = 0; $i < 9; $i++) {
            $this->order(['deadline' => today()->subDay()]);
        }
        $this->order(['deadline' => today()->addDay()]);
        $this->order(['status' => 'completed', 'deadline' => today()->addDays(10)]);
        $this->order(['status' => 'cancelled', 'deadline' => today()->subDay()]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $financials = $response->viewData('financials');
        $this->assertSame(50000000, $financials['critical_receivable']);
        $this->assertSame(5000000, $financials['active_receivable']);
        $this->assertSame(50000000, $financials['total_wip_pelunasan']);
        $this->assertCount(6, $response->viewData('criticalOrders'));
        $this->assertCount(6, $response->viewData('wipOrders'));
        $this->assertSame(0, $response->viewData('wipOrders')->first()['progress_pct']);
    }

    public function test_monthly_prive_is_zero_when_only_previous_month_has_expenses(): void
    {
        OperationalExpense::create(['reference_no' => 'EXP-PAST', 'title' => 'Prive Bulan Lalu',
            'expense_type' => 'personal', 'category' => 'prive_owner', 'amount' => 900000,
            'expense_date' => today()->startOfMonth()->subDay(), 'payment_method' => 'cash']);
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(0, $response->viewData('financials')['total_prive_month']);
        $this->assertCount(0, $response->viewData('personalExpensesList'));
        $response->assertDontSee('Prive Bulan Lalu');
    }

    public function test_cost_composition_includes_legacy_categories_and_prive_uses_real_titles(): void
    {
        $order = $this->order();
        foreach (['aksesoris' => 400000, 'kategori_lama' => 600000] as $category => $amount) {
            $order->costs()->create(['category' => $category, 'description' => 'Belanja', 'amount' => $amount, 'spent_at' => today()]);
        }
        for ($i = 0; $i < 9; $i++) {
            OperationalExpense::create(['reference_no' => 'PRIVE-'.$i, 'title' => 'Kebutuhan Keluarga '.$i,
                'expense_type' => 'personal', 'category' => 'prive_owner', 'amount' => 100000,
                'expense_date' => today(), 'payment_method' => 'cash']);
        }
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Aksesoris & Trims')->assertSee('Biaya Lainnya')->assertSee('Kebutuhan Keluarga');
        $composition = $response->viewData('costComposition');
        $this->assertSame(1000000, $composition->sum('amount'));
        $this->assertEquals(100, $composition->sum('percentage'));
        $this->assertCount(7, $response->viewData('personalExpensesList'));
        $this->assertSame(900000, $response->viewData('financials')['total_prive_month']);
    }

    public function test_trends_include_loans_show_losses_and_keep_prive_separate_from_profit(): void
    {
        $order = $this->order(['grand_total' => 1000000]);
        $order->costs()->create(['category' => 'kain', 'description' => 'Kain', 'amount' => 2000000, 'spent_at' => today()]);
        foreach (['personal' => 300000, 'convection' => 100000] as $type => $amount) {
            OperationalExpense::create(['reference_no' => 'EXP-'.$type, 'title' => 'Beban '.$type,
                'expense_type' => $type, 'category' => 'lain_lain', 'amount' => $amount,
                'expense_date' => today(), 'payment_method' => 'cash']);
        }
        $loan = Loan::create(['reference_no' => 'LOAN-UJI', 'lender_name' => 'Kreditur Uji', 'amount' => 4000000,
            'loan_date' => today(), 'amount_paid' => 500000, 'status' => 'partial']);
        $loan->payments()->create(['amount' => 500000, 'payment_date' => today(), 'payment_method' => 'cash']);
        ConvectionAsset::create(['code' => 'AST-UJI', 'name' => 'Mesin Habis Susut', 'category' => 'machinery',
            'quantity' => 1, 'purchase_price' => 10000000, 'current_value' => 0, 'condition' => 'damaged']);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Beban personal');
        $financials = $response->viewData('financials');
        $trend = $response->viewData('monthlyTrend');
        $this->assertSame('Maret 2026', $trend->first()['full']);
        $this->assertCount(8, $trend->pluck('full')->unique());
        $this->assertSame(-1000000, $financials['gross_profit']);
        $this->assertSame(-1100000, $financials['net_profit']);
        $this->assertSame(0, $financials['aset_mesin']);
        $this->assertSame(-900000, $financials['kas_bersih']);
        $this->assertSame(4000000, $trend->last()['kas_masuk']);
        $this->assertSame(2900000, $trend->last()['kas_keluar']);
        $this->assertSame(-1100000, $trend->last()['laba_bersih']);
        $this->assertSame($trend->last()['kas_keluar'], $financials['this_month_global_outflow']);
    }
}
