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
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'revenue' => $revenue,
            'monthly' => $monthly,
            'monthlyMax' => max(1, $monthly->max('amount')),
            'actionRequired' => $actionRequired,
            'recentOrders' => Order::with(['customer', 'items'])->latest()->take(8)->get(),
            'recentInquiries' => Inquiry::latest()->take(5)->get(),
        ]);
    }
}
