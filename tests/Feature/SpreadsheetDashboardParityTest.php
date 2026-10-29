<?php

namespace Tests\Feature;

use App\Models\ConvectionAsset;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SpreadsheetDashboardParityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(12, 0));
        $this->admin = User::factory()->create();
    }

    private function seedOverview(): void
    {
        $customer = Customer::create(['name' => 'Pelanggan Paritas']);
        $categories = [...array_keys(OrderCost::CATEGORIES), 'kategori_lama', 'kategori_lama_lain'];
        for ($i = 0; $i < 14; $i++) {
            $order = Order::create(['order_number' => 'PARITAS-'.$i, 'customer_id' => $customer->id,
                'name' => 'Seragam '.$i, 'grand_total' => 5000000, 'subtotal' => 5000000,
                'status' => $i === 13 ? 'cancelled' : ($i === 12 ? 'completed' : 'active'),
                'current_stage' => $i % 7 + 1, 'deadline' => $i === 12 ? null : ($i < 10 ? '2026-10-20' : '2026-11-10')]);
            $order->forceFill(['created_at' => $i % 2 ? '2026-10-10 10:00:00' : '2026-09-10 10:00:00'])->save();
            $order->createInitialStages();
            $order->stages()->where('stage_number', '<=', $i % 7)->update(['status' => 'completed']);
            $order->payments()->create(['type' => Payment::TYPE_DP, 'amount' => 1500000, 'payment_date' => '2026-09-20', 'method' => 'cash']);
            if ($i % 3 === 0) {
                $order->payments()->create(['type' => Payment::TYPE_SETTLEMENT, 'amount' => 3500000, 'payment_date' => '2026-10-25', 'method' => 'cash']);
            }
            $order->refreshPaymentStatus();
            $order->costs()->create(['category' => $categories[$i % count($categories)], 'description' => 'Belanja '.$i,
                'amount' => $i === 0 ? 6000000 : 2000000, 'spent_at' => $i % 2 ? '2026-10-15' : '2026-09-15']);
        }
        for ($i = 0; $i < 12; $i++) {
            OperationalExpense::create(['reference_no' => 'EXP-PARITAS-'.$i, 'title' => 'Pengeluaran '.$i,
                'expense_type' => $i < 10 ? 'personal' : 'convection', 'category' => 'lain_lain', 'amount' => 300000,
                'expense_date' => $i === 9 ? '2026-09-15' : ($i === 10 ? '2026-11-15' : '2026-10-15'), 'payment_method' => 'cash']);
        }
        $loan = Loan::create(['reference_no' => 'PIN-PARITAS', 'lender_name' => 'Kreditur', 'amount' => 12000000, 'loan_date' => '2026-10-01']);
        $loan->payments()->create(['reference_no' => 'CICIL-PARITAS', 'amount' => 2000000, 'payment_date' => '2026-10-15', 'payment_method' => 'cash']);
        foreach (['machinery' => 12000000, 'office_tech' => 0, 'other' => 3000000] as $category => $value) {
            ConvectionAsset::create(['code' => 'AST-'.$category, 'name' => $category, 'category' => $category,
                'quantity' => 2, 'purchase_price' => 20000000, 'current_value' => $value, 'condition' => 'good']);
        }
    }

    private function payload(): array
    {
        $service = app(GoogleSheetService::class);

        return [
            'orders' => Order::with(['customer', 'payments', 'items', 'costs', 'stages'])->latest()->get()->map(fn ($row) => $service->formatOrderData($row))->all(),
            'costs' => OrderCost::with('order')->get()->map(fn ($row) => $service->formatCostData($row))->all(),
            'payments' => Payment::with('order.customer')->get()->map(fn ($row) => $service->formatPaymentData($row))->all(),
            'expenses' => OperationalExpense::all()->map(fn ($row) => $service->formatExpenseData($row))->all(),
            'loans' => Loan::all()->map(fn ($row) => $service->formatLoanData($row))->all(),
            'loan_payments' => LoanPayment::with('loan')->get()->map(fn ($row) => $service->formatLoanPaymentData($row))->all(),
            'assets' => ConvectionAsset::all()->map(fn ($row) => $service->formatAssetData($row))->all(),
        ];
    }

    private function spreadsheet(array $payload, array $options = []): array
    {
        $process = new Process(['node', base_path('tests/Support/spreadsheet-runtime.cjs')]);
        $process->setTimeout(30);
        $process->setInput(json_encode(array_merge(['day' => today()->toDateString(), 'payload' => $payload], $options), JSON_THROW_ON_ERROR));
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function assertMatchesAdmin(array $sheet): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $financials = $response->viewData('financials');
        $expected = array_intersect_key($financials, $sheet['financials']);
        $expected['order_value'] = $response->viewData('revenue')['order_value'];
        $expected['operating_cost'] = $financials['total_hpp'] + $financials['convection_expenses_total'];
        $this->assertEquals($expected, $sheet['financials'], 'Metrik dashboard berbeda.');
        $this->assertEquals($response->viewData('monthlyTrend')->all(), $sheet['monthly_trend']);
        $this->assertEquals($response->viewData('costComposition')->map(fn ($row) => array_diff_key($row, ['key' => true]))->all(), $sheet['cost_composition']);
        $this->assertSame($response->viewData('criticalOrders')->pluck('order_number')->all(), array_column($sheet['critical_orders'], 'order_number'));
        $this->assertSame($response->viewData('wipOrders')->pluck('order_number')->all(), array_column($sheet['wip_orders'], 'order_number'));
        $this->assertSame($response->viewData('wipOrders')->pluck('progress_pct')->all(), array_column($sheet['wip_orders'], 'progress_pct'));
        $this->assertSame($response->viewData('personalExpensesList')->pluck('reference_no')->all(), array_column($sheet['personal_expenses'], 'reference_no'));
    }

    public function test_all_metrics_and_six_detail_blocks_match_admin_after_sync_and_rebuild(): void
    {
        $this->seedOverview();
        $sheet = $this->spreadsheet($this->payload(), ['rebuild' => true]);
        $this->assertMatchesAdmin($sheet);
        $this->assertSame([101, 102], array_column($sheet['charts'], 'id'));
        $this->assertSame([48, 48], array_column($sheet['charts'], 'row'));
        $this->assertSame('Tren Dashboard', $sheet['charts'][0]['ranges'][0][0]);
        $this->assertSame(9, $sheet['charts'][0]['ranges'][0][3]);
        $this->assertCount(22, $sheet['order_rows'][0]);
        $keys = [
            ['current_cash_balance', 'total_cash_advances', 'total_active_debt', 'kas_bersih'],
            ['this_month_cash_inflow', 'this_month_global_outflow', 'critical_receivable', 'active_receivable'],
            ['order_value', 'operating_cost', 'net_profit', 'retained_earnings'],
            ['aset_mesin', 'aset_lain', 'total_aset_investasi', 'total_equity'],
        ];
        foreach ($keys as $row => $columns) {
            foreach ($columns as $column => $key) {
                $this->assertSame($sheet['financials'][$key], $sheet['sheet']['grid'][(4 + $row * 3).','.(1 + $column * 3)]['value']);
            }
        }
    }

    public function test_incremental_updates_and_deletions_match_admin_without_full_sync(): void
    {
        $this->seedOverview();
        $before = $this->payload();
        $service = app(GoogleSheetService::class);
        $order = Order::where('order_number', 'PARITAS-1')->firstOrFail();
        $cost = $order->costs()->firstOrFail();
        $costId = 'CST-'.$cost->id;
        $cost->delete();
        $payment = $order->payments()->firstOrFail();
        $paymentId = 'PAY-'.$payment->id;
        $payment->delete();
        $order->refreshPaymentStatus();
        $order->update(['deadline' => '2026-12-01']);
        $expense = OperationalExpense::firstOrFail();
        $expense->update(['expense_type' => 'convection', 'amount' => 900000]);
        $asset = ConvectionAsset::firstOrFail();
        $assetCode = $asset->code;
        $asset->delete();
        $operations = [
            ['action' => 'delete', 'entity' => 'cost', 'id' => $costId],
            ['action' => 'delete', 'entity' => 'payment', 'id' => $paymentId],
            ['type' => 'order', 'order' => $service->formatOrderData($order->fresh())],
            ['type' => 'expense', 'expense' => $service->formatExpenseData($expense->fresh())],
            ['action' => 'delete', 'entity' => 'asset', 'id' => $assetCode],
        ];
        $this->assertMatchesAdmin($this->spreadsheet($before, ['operations' => $operations]));
    }

    public function test_empty_dashboard_has_zero_totals_and_chart_installation_instructions(): void
    {
        $sheet = $this->spreadsheet($this->payload(), ['withCharts' => false]);
        $this->assertMatchesAdmin($sheet);
        $this->assertSame([], $sheet['charts']);
        $this->assertStringContainsString('Insert > Chart', $sheet['sheet']['grid']['49,1']['value']);
        $this->assertStringContainsString('Line chart', $sheet['sheet']['grid']['49,7']['value']);
    }

    public function test_losses_and_the_eight_month_window_match_at_the_year_boundary(): void
    {
        $this->travelTo(now()->setDate(2027, 1, 31));
        $customer = Customer::create(['name' => 'Uji Kerugian']);
        $order = Order::create(['order_number' => 'RUGI', 'customer_id' => $customer->id, 'name' => 'Uji',
            'grand_total' => 100000, 'subtotal' => 100000, 'status' => 'active']);
        $order->costs()->create(['category' => 'kain', 'description' => 'Rugi', 'amount' => 200050, 'spent_at' => today()]);
        $this->assertMatchesAdmin($this->spreadsheet($this->payload()));
    }

    public function test_deletion_routes_send_the_final_order_and_loan_balances(): void
    {
        $this->seedOverview();
        Setting::set('google_sheet_webhook_url', 'https://example.invalid/webhook');
        Setting::set('google_sheet_auto_sync', '1');
        Http::fake(['https://example.invalid/webhook' => Http::response(['status' => 'success'])]);
        $order = Order::where('order_number', 'PARITAS-1')->firstOrFail();
        $cost = $order->costs()->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.orders.costs.destroy', ['order' => $order, 'cost' => $cost]))->assertRedirect();
        $lastOrder = Http::recorded()->filter(fn ($entry) => ($entry[0]->data()['type'] ?? '') === 'order')->last()[0]->data()['order'];
        $this->assertSame(0, $lastOrder['total_cost']);
        $this->assertSame(1500000, $lastOrder['dp_vs_hpp']);

        $payment = $order->payments()->firstOrFail();
        $this->delete(route('admin.payments.destroy', $payment))->assertRedirect();
        $lastOrder = Http::recorded()->filter(fn ($entry) => ($entry[0]->data()['type'] ?? '') === 'order')->last()[0]->data()['order'];
        $this->assertSame(0, $lastOrder['amount_paid']);
        $this->assertSame(0, $lastOrder['dp']);
        $this->assertSame(5000000, $lastOrder['remaining']);

        $loan = Loan::firstOrFail();
        $loanPayment = $loan->payments()->firstOrFail();
        $this->delete(route('admin.loans.payments.destroy', ['loan' => $loan, 'payment' => $loanPayment]))->assertRedirect();
        $lastLoan = Http::recorded()->filter(fn ($entry) => ($entry[0]->data()['type'] ?? '') === 'loan')->last()[0]->data()['loan'];
        $this->assertSame(12000000, $lastLoan['remaining_amount']);
        $this->assertSame(0, $lastLoan['amount_paid']);
    }
}
