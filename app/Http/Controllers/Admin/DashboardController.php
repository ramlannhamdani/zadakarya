<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Order;

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
            'actionRequired' => $actionRequired,
            'recentOrders' => Order::with(['customer', 'items'])->latest()->take(8)->get(),
            'recentInquiries' => Inquiry::latest()->take(5)->get(),
        ]);
    }
}
