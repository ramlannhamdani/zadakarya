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

    public function test_paid_order_shows_cash_position_in_admin_and_keeps_dp_realization_in_sheet(): void
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
        $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()
            ->assertSee('+Rp 2.000.000')->assertSee('Surplus Kas')->assertDontSee('DP Kurang');
        $this->get(route('admin.orders.show', ['order' => $order, 'tab' => 'hpp']))->assertOk()
            ->assertSee('Pembayaran Masuk vs HPP')->assertSee('+Rp 2.000.000')->assertDontSee('-Rp 1.000.000');

        // Kolom ringkasan pembayaran tanpa bukti jenis tidak boleh dianggap DP.
        $legacy = $this->order(['amount_paid' => 1500000]);
        $this->assertSame(0, $legacy->dp_paid);
    }

    public function test_full_payment_without_dp_shows_actual_cash_position(): void
    {
        foreach ([225500 => ['+Rp 24.500', 'Surplus Kas'], 250000 => ['Rp 0', 'Impas Kas'], 275000 => ['-Rp 25.000', 'Defisit Kas']] as $hpp => [$position, $label]) {
            $order = $this->order(['grand_total' => 250000, 'status' => 'completed', 'current_stage' => 7]);
            $order->payments()->create(['amount' => 250000, 'type' => Payment::TYPE_SETTLEMENT, 'payment_date' => today(), 'method' => 'cash']);
            $order->costs()->create(['category' => 'kain', 'description' => 'Kain', 'amount' => $hpp, 'spent_at' => today()]);
            $order->refreshPaymentStatus();

            $this->actingAs($this->admin)->get(route('admin.orders.index', ['q' => $order->order_number]))->assertOk()
                ->assertSee($position)->assertSee($label)->assertDontSee('DP Kurang');
        }
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

    public function test_closing_final_stage_moves_outstanding_balance_to_due_receivables(): void
    {
        $order = $this->order(['grand_total' => 4320000, 'current_stage' => 7, 'deadline' => today()->addWeek()]);
        $stage = $order->stages()->create(['stage_number' => 7, 'name' => 'Selesai', 'status' => 'in_progress']);
        $order->payments()->create(['amount' => 2000000, 'type' => Payment::TYPE_DP, 'payment_date' => today(), 'method' => 'cash']);
        $order->costs()->create(['category' => 'kain', 'description' => 'Kain', 'amount' => 3105380, 'spent_at' => today()]);
        $order->refreshPaymentStatus();

        $list = route('admin.orders.index');
        $this->actingAs($this->admin)->get($list)->assertOk()
            ->assertSee('-Rp 1.105.380')->assertSee('Nombok')->assertSee('Belum ditutup');
        $before = $this->get(route('admin.dashboard'))->assertOk()->viewData('financials');
        $this->assertSame(-1105380, $before['current_cash_balance']);
        $this->assertSame(1105380, $before['total_cash_advances']);
        $this->assertSame(2320000, $before['active_receivable']);

        $this->from($list)->post(route('admin.orders.stages.complete', [$order, $stage]))->assertRedirect($list);
        $this->get(route('admin.orders.index', ['filter' => 'ready_to_bill']))->assertOk()
            ->assertSee($order->order_number)->assertSee('Siap Ditagih')->assertSee('Rp 2.320.000')
            ->assertSee('Rp 0')->assertDontSee('-Rp 1.105.380');
        $this->get(route('admin.orders.show', ['order' => $order, 'tab' => 'hpp']))->assertOk()
            ->assertSee('Sisa tagihan Rp 2.320.000 siap ditagih.')->assertDontSee('-Rp 1.105.380');

        $dashboard = $this->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(-1105380, $dashboard->viewData('financials')['current_cash_balance']);
        $this->assertSame(2320000, $dashboard->viewData('financials')['critical_receivable']);
        $this->assertSame(0, $dashboard->viewData('financials')['total_cash_advances']);
        $this->assertSame(0, $dashboard->viewData('financials')['total_wip_pelunasan']);
        $this->assertSame(0, app(GoogleSheetService::class)->formatOrderData($order->fresh())['dp_vs_hpp']);

        $this->from($list)->post(route('admin.orders.stages.reopen', [$order, $stage]))->assertRedirect($list);
        $this->get($list)->assertOk()->assertSee('-Rp 1.105.380');
    }

    public function test_only_completed_unpaid_orders_count_toward_due_receivables(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->order(['status' => 'completed', 'deadline' => today()->addDays(10)]);
        }
        $late = $this->order(['deadline' => today()->subDay()]);
        $late->costs()->create(['category' => 'kain', 'description' => 'Kain', 'amount' => 1000000, 'spent_at' => today()]);
        $this->order(['deadline' => today()->addDay()]);
        $this->order(['status' => 'completed', 'amount_paid' => 5000000, 'payment_status' => 'paid']);
        $this->order(['status' => 'cancelled', 'deadline' => today()->subDay()]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $financials = $response->viewData('financials');
        $this->assertSame(40000000, $financials['critical_receivable']);
        $this->assertSame(10000000, $financials['active_receivable']);
        $this->assertSame(1000000, $financials['total_cash_advances']);
        $this->assertSame(10000000, $financials['total_wip_pelunasan']);
        $criticalOrders = $response->viewData('criticalOrders');
        $this->assertCount(6, $criticalOrders);
        $this->assertNotContains($late->order_number, $criticalOrders->pluck('order_number'));
        $this->assertCount(2, $response->viewData('wipOrders'));
        $this->assertSame(0, $response->viewData('wipOrders')->first()['progress_pct']);
    }

    public function test_cash_advances_sum_shortfalls_per_active_unpaid_order(): void
    {
        foreach ([3500000, 1005000, 507500, -8453019, -3067800] as $position) {
            $order = $this->order(['grand_total' => 20000000]);
            $order->payments()->create(['amount' => 9000000 + $position, 'type' => Payment::TYPE_DP, 'payment_date' => today(), 'method' => 'cash']);
            $order->costs()->create(['category' => 'kain', 'description' => 'Kain', 'amount' => 9000000, 'spent_at' => today()]);
            $order->refreshPaymentStatus();
        }

        $dashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(11520819, $dashboard->viewData('financials')['total_cash_advances']);
        $this->assertSame(-6508319, $dashboard->viewData('financials')['current_cash_balance']);
        $this->assertSame(2, $dashboard->viewData('financials')['deficit_orders_count']);

        $surplus = $this->order(['grand_total' => 20000000]);
        $surplus->payments()->create(['amount' => 7000000, 'type' => Payment::TYPE_DP, 'payment_date' => today(), 'method' => 'cash']);
        $surplus->refreshPaymentStatus();
        $dashboard = $this->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(11520819, $dashboard->viewData('financials')['total_cash_advances']);
        $this->assertSame(491681, $dashboard->viewData('financials')['current_cash_balance']);
        $this->assertSame(2, $dashboard->viewData('financials')['deficit_orders_count']);
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

        $previousMonth = today()->startOfMonth()->subMonth();
        $response = $this->get(route('admin.dashboard', ['prive_month' => $previousMonth->format('Y-m')]))->assertOk()
            ->assertSee('Prive Bulan Lalu')->assertSee('Total '.$previousMonth->translatedFormat('F Y'));
        $this->assertSame(900000, $response->viewData('financials')['total_prive_month']);
        $this->assertSame(0, $response->viewData('financials')['personal_expenses_this_month']);
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
        $this->assertSame(1100000, $financials['current_cash_balance']);
        $this->assertSame(4000000, $trend->last()['kas_masuk']);
        $this->assertSame(2900000, $trend->last()['kas_keluar']);
        $this->assertSame(-1100000, $trend->last()['laba_bersih']);
        $this->assertSame($trend->last()['kas_keluar'], $financials['this_month_global_outflow']);
    }
}
