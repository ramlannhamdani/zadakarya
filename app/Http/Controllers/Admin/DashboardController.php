<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Payment;

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
        $nonCancelled = Order::where('status', '!=', 'cancelled')->get(['grand_total', 'amount_paid']);
        $revenue = [
            'total' => (int) Payment::sum('amount'),
            'this_month' => (int) Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'receivable' => (int) $nonCancelled->sum(fn ($o) => max(0, $o->grand_total - $o->amount_paid)),
            'order_value' => (int) $nonCancelled->sum('grand_total'),
        ];

        // Laba Kotor, Beban Operasional, dan Laba Bersih Perusahaan (Fail-safe bila belum migrasi)
        $totalHpp = 0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('order_costs')) {
                $totalHpp = (int) \App\Models\OrderCost::whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))->sum('amount');
            }
        } catch (\Throwable) {
            $totalHpp = 0;
        }

        $grossProfit = max(0, $revenue['order_value'] - $totalHpp);

        $totalExpenses = 0;
        $thisMonthExpenses = 0;
        $convectionExpensesTotal = 0;
        $convectionExpensesThisMonth = 0;
        $personalExpensesTotal = 0;
        $personalExpensesThisMonth = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('operational_expenses')) {
                $nowStart = now()->startOfMonth();
                $nowEnd = now()->endOfMonth();

                $totalExpenses = (int) \App\Models\OperationalExpense::sum('amount');
                $thisMonthExpenses = (int) \App\Models\OperationalExpense::whereBetween('expense_date', [$nowStart, $nowEnd])->sum('amount');

                $convectionExpensesTotal = (int) \App\Models\OperationalExpense::where('expense_type', 'convection')->sum('amount');
                $convectionExpensesThisMonth = (int) \App\Models\OperationalExpense::where('expense_type', 'convection')->whereBetween('expense_date', [$nowStart, $nowEnd])->sum('amount');

                $personalExpensesTotal = (int) \App\Models\OperationalExpense::where('expense_type', 'personal')->sum('amount');
                $personalExpensesThisMonth = (int) \App\Models\OperationalExpense::where('expense_type', 'personal')->whereBetween('expense_date', [$nowStart, $nowEnd])->sum('amount');
            }
        } catch (\Throwable) {
            $totalExpenses = 0;
            $thisMonthExpenses = 0;
            $convectionExpensesTotal = 0;
            $convectionExpensesThisMonth = 0;
            $personalExpensesTotal = 0;
            $personalExpensesThisMonth = 0;
        }

        // Laba bersih operasional konveksi (Laba Kotor - Beban Konveksi)
        $netProfit = $grossProfit - $convectionExpensesTotal;

        // Total Dana Talangan Kas yang sedang terpakai menombok pesanan aktif (HPP > Uang Masuk)
        $totalCashAdvances = 0;
        $deficitOrdersCount = 0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('order_costs')) {
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

        $financials = [
            'total_hpp' => $totalHpp,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'this_month_expenses' => $thisMonthExpenses,
            'convection_expenses_total' => $convectionExpensesTotal,
            'convection_expenses_this_month' => $convectionExpensesThisMonth,
            'personal_expenses_total' => $personalExpensesTotal,
            'personal_expenses_this_month' => $personalExpensesThisMonth,
            'net_profit' => $netProfit,
            'total_cash_advances' => $totalCashAdvances,
            'deficit_orders_count' => $deficitOrdersCount,
        ];

        // Grafik 6 bulan terakhir (pembayaran per bulan).
        $start = now()->subMonths(5)->startOfMonth();
        $byMonth = Payment::where('payment_date', '>=', $start)
            ->get(['amount', 'payment_date'])
            ->groupBy(fn ($p) => $p->payment_date->format('Y-m'))
            ->map(fn ($g) => (int) $g->sum('amount'));

        $monthly = collect(range(0, 5))->map(function ($i) use ($start, $byMonth) {
            $month = $start->copy()->addMonths($i);

            return [
                'label' => $month->translatedFormat('M'),
                'full' => $month->translatedFormat('F Y'),
                'amount' => $byMonth[$month->format('Y-m')] ?? 0,
            ];
        });

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
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'revenue' => $revenue,
            'financials' => $financials,
            'monthly' => $monthly,
            'monthlyMax' => max(1, $monthly->max('amount')),
            'actionRequired' => $actionRequired,
            'recentOrders' => Order::with(['customer', 'items'])->latest()->take(8)->get(),
            'recentInquiries' => Inquiry::latest()->take(5)->get(),
        ]);
    }
}
