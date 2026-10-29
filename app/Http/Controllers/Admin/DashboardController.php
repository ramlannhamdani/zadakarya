<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConvectionAsset;
use App\Models\Inquiry;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Support\Stages;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'active_orders' => Order::where('status', 'active')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'unpaid_orders' => Order::where('status', 'active')->whereIn('payment_status', ['unpaid', 'partial'])->count(),
            'new_inquiries' => Inquiry::where('status', 'new')->count(),
        ];

        // Revenue: uang yang benar-benar masuk (tabel payments), piutang, dan nilai pesanan.
        $nonCancelled = Order::where('status', '!=', 'cancelled')->get(['id', 'status', 'grand_total', 'amount_paid', 'payment_status']);
        $completedUnpaidOrders = $nonCancelled->filter(fn ($o) => $o->status === 'completed' && $o->payment_status !== 'paid');
        $activeUnpaidOrders = $nonCancelled->filter(fn ($o) => $o->status === 'active' && $o->payment_status !== 'paid');

        $completedReceivable = (int) $completedUnpaidOrders->sum(fn ($o) => max(0, $o->grand_total - $o->amount_paid));
        $activeReceivable = (int) $activeUnpaidOrders->sum(fn ($o) => max(0, $o->grand_total - $o->amount_paid));

        $revenue = [
            'total' => (int) Payment::sum('amount'),
            'this_month' => (int) Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'receivable' => (int) $nonCancelled->sum(fn ($o) => max(0, $o->grand_total - $o->amount_paid)),
            'completed_receivable' => $completedReceivable,
            'completed_unpaid_count' => $completedUnpaidOrders->count(),
            'active_receivable' => $activeReceivable,
            'active_unpaid_count' => $activeUnpaidOrders->count(),
            'order_value' => (int) $nonCancelled->sum('grand_total'),
        ];

        // Laba Kotor, Beban Operasional, dan Laba Bersih Perusahaan (Fail-safe bila belum migrasi)
        $nowStart = now()->startOfMonth();
        $nowEnd = now()->endOfMonth();

        $totalHpp = 0;
        $thisMonthHpp = 0;
        try {
            if (Schema::hasTable('order_costs')) {
                $totalHpp = (int) OrderCost::whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))->sum('amount');
                $thisMonthHpp = (int) OrderCost::whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))
                    ->whereBetween('spent_at', [$nowStart, $nowEnd])
                    ->sum('amount');
            }
        } catch (\Throwable) {
            $totalHpp = 0;
            $thisMonthHpp = 0;
        }

        $grossProfit = $revenue['order_value'] - $totalHpp;

        $totalExpenses = 0;
        $thisMonthExpenses = 0;
        $convectionExpensesTotal = 0;
        $convectionExpensesThisMonth = 0;
        $personalExpensesTotal = 0;
        $personalExpensesThisMonth = 0;

        try {
            if (Schema::hasTable('operational_expenses')) {
                $totalExpenses = (int) OperationalExpense::sum('amount');
                $thisMonthExpenses = (int) OperationalExpense::whereBetween('expense_date', [$nowStart, $nowEnd])->sum('amount');

                $convectionExpensesTotal = (int) OperationalExpense::where('expense_type', 'convection')->sum('amount');
                $convectionExpensesThisMonth = (int) OperationalExpense::where('expense_type', 'convection')->whereBetween('expense_date', [$nowStart, $nowEnd])->sum('amount');

                $personalExpensesTotal = (int) OperationalExpense::where('expense_type', 'personal')->sum('amount');
                $personalExpensesThisMonth = (int) OperationalExpense::where('expense_type', 'personal')->whereBetween('expense_date', [$nowStart, $nowEnd])->sum('amount');
            }
        } catch (\Throwable) {
            $totalExpenses = 0;
            $thisMonthExpenses = 0;
            $convectionExpensesTotal = 0;
            $convectionExpensesThisMonth = 0;
            $personalExpensesTotal = 0;
            $personalExpensesThisMonth = 0;
        }

        // Total Pengeluaran Kas Global (HPP + Ops. Konveksi + Ops. Pribadi/Prive)
        $totalGlobalOutflow = $totalHpp + $totalExpenses;
        $thisMonthGlobalOutflow = $thisMonthHpp + $thisMonthExpenses;

        // Persentase porsi pengeluaran global
        $hppShare = $totalGlobalOutflow > 0 ? round(($totalHpp / $totalGlobalOutflow) * 100, 1) : 0;
        $convectionShare = $totalGlobalOutflow > 0 ? round(($convectionExpensesTotal / $totalGlobalOutflow) * 100, 1) : 0;
        $personalShare = $totalGlobalOutflow > 0 ? round(($personalExpensesTotal / $totalGlobalOutflow) * 100, 1) : 0;

        // Laba bersih operasional konveksi (Laba Kotor - Beban Konveksi)
        $netProfit = $grossProfit - $convectionExpensesTotal;

        // Total Dana Talangan Kas yang sedang terpakai menombok pesanan aktif (HPP > Uang Masuk)
        $totalCashAdvances = 0;
        $deficitOrdersCount = 0;
        try {
            if (Schema::hasTable('order_costs')) {
                $activeUnsettled = Order::with('costs')
                    ->where('status', 'active')
                    ->where('payment_status', '!=', 'paid')
                    ->get();

                foreach ($activeUnsettled as $actOrder) {
                    if ($actOrder->cash_deficit > 0) {
                        $totalCashAdvances += $actOrder->cash_deficit;
                        $deficitOrdersCount++;
                    }
                }
            }
        } catch (\Throwable) {
            $totalCashAdvances = 0;
            $deficitOrdersCount = 0;
        }

        $totalActiveDebt = 0;
        $totalLoanInflow = 0;
        try {
            if (Schema::hasTable('loans')) {
                $totalActiveDebt = (int) Loan::whereIn('status', [Loan::STATUS_UNPAID, Loan::STATUS_PARTIAL])
                    ->get()
                    ->sum(fn ($l) => $l->remaining_amount);
                $totalLoanInflow = (int) Loan::sum('amount');
            }
        } catch (\Throwable) {
            $totalActiveDebt = 0;
            $totalLoanInflow = 0;
        }

        $totalLoanPayments = 0;
        try {
            if (Schema::hasTable('loan_payments')) {
                $totalLoanPayments = (int) LoanPayment::sum('amount');
            }
        } catch (\Throwable) {
            $totalLoanPayments = 0;
        }

        $totalCashInflow = $revenue['total'] + $totalLoanInflow;
        $totalCashOutflow = (int) OrderCost::sum('amount') + $totalExpenses + $totalLoanPayments;
        $currentCashBalance = $totalCashInflow - $totalCashOutflow;
        $revenue['current_cash_balance'] = $currentCashBalance;

        // Nilai buku nol tetap nol, termasuk aset yang telah habis disusutkan.
        $machineryCategories = [
            ConvectionAsset::CATEGORY_MACHINERY,
            ConvectionAsset::CATEGORY_CUTTING_FINISHING,
            ConvectionAsset::CATEGORY_PRINTING_EMBROIDERY,
            ConvectionAsset::CATEGORY_FINISHING_STEAMER,
        ];
        $asetMesin = (int) ConvectionAsset::whereIn('category', $machineryCategories)->sum('current_value');
        $asetLain = (int) ConvectionAsset::whereNotIn('category', $machineryCategories)->sum('current_value');
        $totalAsetInvestasi = $asetMesin + $asetLain;
        $kasBersih = $currentCashBalance - $totalCashAdvances;
        $totalEquity = $currentCashBalance + $revenue['receivable'] + $totalAsetInvestasi - $totalActiveDebt;

        // Total dihitung sebelum batas pratinjau agar kartu mencakup seluruh tagihan.
        $criticalQuery = Order::where('status', '!=', 'cancelled')
            ->whereColumn('grand_total', '>', 'amount_paid')
            ->where(function ($query) {
                $query->where('status', 'completed')->orWhereDate('deadline', '<=', today());
            });
        $criticalReceivable = (int) (clone $criticalQuery)->get(['grand_total', 'amount_paid'])->sum('remaining');
        $criticalOrders = (clone $criticalQuery)->with('customer')->orderBy('deadline')->orderBy('id')->limit(6)->get();
        $runningReceivable = (int) Order::where('status', 'active')
            ->where(fn ($query) => $query->whereNull('deadline')->orWhereDate('deadline', '>', today()))
            ->get(['grand_total', 'amount_paid'])->sum('remaining');

        $rawCosts = OrderCost::whereHas('order', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->selectRaw('category, sum(amount) as total_amount')->groupBy('category')->pluck('total_amount', 'category');
        $costComposition = collect(OrderCost::CATEGORIES)->map(function ($label, $key) use ($rawCosts, $totalHpp) {
            $amount = (int) ($rawCosts[$key] ?? 0);

            return ['key' => $key, 'name' => $label, 'amount' => $amount,
                'percentage' => $totalHpp > 0 ? round($amount / $totalHpp * 100, 1) : 0];
        })->values();
        $otherCost = (int) $rawCosts->except(array_keys(OrderCost::CATEGORIES))->sum();
        if ($otherCost > 0) {
            $costComposition->push(['key' => 'other', 'name' => 'Biaya Lainnya', 'amount' => $otherCost,
                'percentage' => $totalHpp > 0 ? round($otherCost / $totalHpp * 100, 1) : 0]);
        }

        $totalWipPelunasan = $activeReceivable;
        $wipOrders = Order::with(['customer', 'stages'])->where('status', 'active')->latest()->latest('id')->limit(6)->get()
            ->map(function ($order) {
                // Progres berasal dari tahap yang benar-benar selesai, bukan angka rekaan per tahap.
                $percentage = (int) round($order->stages->where('status', Stages::STATUS_COMPLETED)->count() / Stages::count() * 100);

                return ['order' => $order, 'order_number' => $order->order_number,
                    'customer' => $order->customer?->name ?? 'Pelanggan',
                    'progress_pct' => $percentage, 'progress_label' => $percentage.'% tahap selesai',
                    'pelunasan' => $order->remaining, 'stage_name' => $order->current_stage_name];
            });

        $personalExpensesList = OperationalExpense::where('expense_type', OperationalExpense::TYPE_PERSONAL)
            ->whereBetween('expense_date', [$nowStart, $nowEnd])->latest('expense_date')->latest('id')->limit(7)->get();
        $totalPriveMonth = $personalExpensesThisMonth;

        $historyStart = now()->startOfMonth()->subMonths(7);
        $historyEnd = now()->endOfMonth();
        $byMonth = fn ($rows, $date) => $rows->groupBy(fn ($row) => $row->{$date}->format('Y-m'))
            ->map(fn ($group) => (int) $group->sum('amount'));
        $inflowByMonth = $byMonth(Payment::whereBetween('payment_date', [$historyStart, $historyEnd])->get(), 'payment_date');
        $loansByMonth = $byMonth(Loan::whereBetween('loan_date', [$historyStart, $historyEnd])->get(), 'loan_date');
        $loanPaymentsByMonth = $byMonth(LoanPayment::whereBetween('payment_date', [$historyStart, $historyEnd])->get(), 'payment_date');
        $costByMonth = $byMonth(OrderCost::whereBetween('spent_at', [$historyStart, $historyEnd])->get(), 'spent_at');
        $profitCostByMonth = $byMonth(OrderCost::whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->whereBetween('spent_at', [$historyStart, $historyEnd])->get(), 'spent_at');
        $expenses = OperationalExpense::whereBetween('expense_date', [$historyStart, $historyEnd])->get();
        $expenseByMonth = $byMonth($expenses, 'expense_date');
        $officeByMonth = $byMonth($expenses->where('expense_type', OperationalExpense::TYPE_CONVECTION), 'expense_date');
        $salesByMonth = Order::where('status', '!=', 'cancelled')->whereBetween('created_at', [$historyStart, $historyEnd])->get()
            ->groupBy(fn ($order) => $order->created_at->format('Y-m'))->map(fn ($group) => (int) $group->sum('grand_total'));

        $monthlyTrend = collect(range(0, 7))->map(function ($i) use ($historyStart, $inflowByMonth, $loansByMonth,
            $loanPaymentsByMonth, $costByMonth, $profitCostByMonth, $expenseByMonth, $officeByMonth, $salesByMonth) {
            $month = $historyStart->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $gross = (int) ($salesByMonth[$key] ?? 0) - (int) ($profitCostByMonth[$key] ?? 0);

            return ['label' => $month->translatedFormat('M'), 'full' => $month->translatedFormat('F Y'),
                'kas_masuk' => (int) ($inflowByMonth[$key] ?? 0) + (int) ($loansByMonth[$key] ?? 0),
                'kas_keluar' => (int) ($costByMonth[$key] ?? 0) + (int) ($expenseByMonth[$key] ?? 0) + (int) ($loanPaymentsByMonth[$key] ?? 0),
                'estimasi_laba' => $gross, 'laba_bersih' => $gross - (int) ($officeByMonth[$key] ?? 0)];
        });
        $thisMonthLoanInflow = (int) ($loansByMonth[now()->format('Y-m')] ?? 0);

        $financials = [
            'total_hpp' => $totalHpp,
            'this_month_hpp' => $thisMonthHpp,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'this_month_expenses' => $thisMonthExpenses,
            'convection_expenses_total' => $convectionExpensesTotal,
            'convection_expenses_this_month' => $convectionExpensesThisMonth,
            'personal_expenses_total' => $personalExpensesTotal,
            'personal_expenses_this_month' => $personalExpensesThisMonth,
            'total_global_outflow' => $totalGlobalOutflow,
            'this_month_global_outflow' => (int) $monthlyTrend->last()['kas_keluar'],
            'this_month_cash_inflow' => $revenue['this_month'] + $thisMonthLoanInflow,
            'hpp_share' => $hppShare,
            'convection_share' => $convectionShare,
            'personal_share' => $personalShare,
            'net_profit' => $netProfit,
            'total_cash_advances' => $totalCashAdvances,
            'deficit_orders_count' => $deficitOrdersCount,
            'total_active_debt' => $totalActiveDebt,
            'current_cash_balance' => $currentCashBalance,
            'completed_receivable' => $completedReceivable,
            'completed_unpaid_count' => $completedUnpaidOrders->count(),
            'active_receivable' => $runningReceivable,
            'active_unpaid_count' => $activeUnpaidOrders->count(),
            // Metrik Dashboard Keuangan
            'kas_bersih' => $kasBersih,
            'critical_receivable' => $criticalReceivable,
            'aset_mesin' => $asetMesin,
            'aset_lain' => $asetLain,
            'total_aset_investasi' => $totalAsetInvestasi,
            'total_equity' => $totalEquity,
            'profit_margin_pct' => $revenue['order_value'] > 0 ? round(($netProfit / $revenue['order_value']) * 100, 1) : 0,
            'retained_earnings' => $netProfit - $personalExpensesTotal,
            'total_wip_pelunasan' => $totalWipPelunasan,
            'total_prive_month' => $totalPriveMonth,
        ];

        $actionRequired = [
            'new_inquiries' => Inquiry::where('status', 'new')->count(),
            'awaiting_dp' => Order::where('status', 'active')->where('payment_status', 'unpaid')->count(),
            'in_production' => Order::where('status', 'active')->where('current_stage', 4)->count(),
            'near_deadline' => Order::where('status', 'active')
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->count(),
            'cash_deficits' => $deficitOrdersCount,
            'cash_deficits_amount' => $totalCashAdvances,
            'completed_unpaid' => $completedUnpaidOrders->count(),
            'completed_receivable' => $completedReceivable,
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'revenue' => $revenue,
            'financials' => $financials,
            'actionRequired' => $actionRequired,
            'recentOrders' => Order::with(['customer', 'items'])->latest()->take(8)->get(),
            'recentInquiries' => Inquiry::latest()->take(5)->get(),
            // Data untuk Dashboard Keuangan (Referensi Gambar 2 & Gambar 3)
            'criticalOrders' => $criticalOrders,
            'costComposition' => $costComposition,
            'wipOrders' => $wipOrders,
            'personalExpensesList' => $personalExpensesList,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }
}
