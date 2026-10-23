<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Services\GoogleSheetService;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OperationalExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->month : (int) now()->format('n');
        $year = $request->filled('year') ? (int) $request->year : (int) now()->format('Y');
        $isAllPeriod = $request->get('period') === 'all';

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

        $query = OperationalExpense::with('recorder');

        // Filter tanggal / bulan
        if (! $isAllPeriod) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        // Filter jenis beban (Ops. Konveksi vs Ops. Pribadi)
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Pencarian teks
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('reference_no', 'like', $term)
                  ->orWhere('notes', 'like', $term);
            });
        }

        $expenses = (clone $query)
            ->latest('expense_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // 1. Metrik Arus Kas Periode Ini (Cash Flow)
        $paymentQuery = Payment::query();
        $hppQuery = OrderCost::query();
        $expensePeriodQuery = OperationalExpense::query();
        $orderPeriodQuery = Order::where('status', '!=', 'cancelled');

        if (! $isAllPeriod) {
            $paymentQuery->whereBetween('payment_date', [$startDate, $endDate]);
            $hppQuery->whereBetween('spent_at', [$startDate, $endDate]);
            $expensePeriodQuery->whereBetween('expense_date', [$startDate, $endDate]);
            $orderPeriodQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $periodIncome = 0;
        $periodHpp = 0;
        $periodExpense = 0;
        $periodConvectionExpense = 0;
        $periodPersonalExpense = 0;
        $periodOrderValue = 0;

        try {
            $periodIncome = (int) $paymentQuery->sum('amount');
            $periodHpp = Schema::hasTable('order_costs') ? (int) $hppQuery->sum('amount') : 0;
            if (Schema::hasTable('operational_expenses')) {
                $periodExpense = (int) $expensePeriodQuery->sum('amount');
                $periodConvectionExpense = (int) (clone $expensePeriodQuery)->where('expense_type', OperationalExpense::TYPE_CONVECTION)->sum('amount');
                $periodPersonalExpense = (int) (clone $expensePeriodQuery)->where('expense_type', OperationalExpense::TYPE_PERSONAL)->sum('amount');
            }
            $periodOrderValue = (int) $orderPeriodQuery->sum('grand_total');
        } catch (\Throwable) {}

        $periodTotalOutflow = $periodHpp + $periodExpense;
        $periodNetBalance = $periodIncome - $periodTotalOutflow;

        // 2. Metrik Laba Periode Ini (Laba Bisnis Konveksi)
        $periodGrossProfit = max(0, $periodOrderValue - $periodHpp);
        $periodNetProfit = $periodGrossProfit - $periodConvectionExpense;

        // 3. Saldo Kas Kumulatif Keseluruhan (Real-time Running Balance)
        $totalAllIncome = 0;
        $totalAllHpp = 0;
        $totalAllExpense = 0;
        $totalAllConvectionExpense = 0;
        $totalAllPersonalExpense = 0;

        try {
            $totalAllIncome = (int) Payment::sum('amount');
            $totalAllHpp = Schema::hasTable('order_costs') ? (int) OrderCost::sum('amount') : 0;
            if (Schema::hasTable('operational_expenses')) {
                $totalAllExpense = (int) OperationalExpense::sum('amount');
                $totalAllConvectionExpense = (int) OperationalExpense::where('expense_type', OperationalExpense::TYPE_CONVECTION)->sum('amount');
                $totalAllPersonalExpense = (int) OperationalExpense::where('expense_type', OperationalExpense::TYPE_PERSONAL)->sum('amount');
            }
        } catch (\Throwable) {}

        $totalAllOutflow = $totalAllHpp + $totalAllExpense;
        $totalCurrentBalance = $totalAllIncome - $totalAllOutflow;

        // Kategori beban terbesar periode ini
        $topCategory = null;
        try {
            $topQuery = OperationalExpense::query();
            if (! $isAllPeriod) {
                $topQuery->whereBetween('expense_date', [$startDate, $endDate]);
            }
            $topCategoryRow = $topQuery->selectRaw('category, SUM(amount) as total')
                ->groupBy('category')
                ->orderByDesc('total')
                ->first();

            if ($topCategoryRow) {
                $topCategory = [
                    'name' => OperationalExpense::CATEGORIES[$topCategoryRow->category] ?? $topCategoryRow->category,
                    'amount' => (int) $topCategoryRow->total,
                ];
            }
        } catch (\Throwable) {}

        // Daftar tahun untuk filter dropdown
        $startYear = now()->format('Y');
        try {
            $earliestYear = OperationalExpense::min('expense_date');
            $startYear = $earliestYear ? (int) Carbon::parse($earliestYear)->format('Y') : (int) now()->format('Y');
        } catch (\Throwable) {}

        $activeAdvances = 0;
        $activeDeficitCount = 0;
        try {
            $deficitOrders = Order::with('costs')
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', '!=', 'paid')
                ->get();

            foreach ($deficitOrders as $ord) {
                if ($ord->cash_deficit > 0) {
                    $activeAdvances += $ord->cash_deficit;
                    $activeDeficitCount++;
                }
            }
        } catch (\Throwable) {}

        $years = range(max($startYear, (int) now()->format('Y') - 3), (int) now()->format('Y') + 1);
        rsort($years);

        return view('admin.expenses.index', compact(
            'expenses',
            'periodIncome',
            'periodHpp',
            'periodExpense',
            'periodConvectionExpense',
            'periodPersonalExpense',
            'periodTotalOutflow',
            'periodNetBalance',
            'periodOrderValue',
            'periodGrossProfit',
            'periodNetProfit',
            'totalCurrentBalance',
            'totalAllIncome',
            'totalAllOutflow',
            'totalAllConvectionExpense',
            'totalAllPersonalExpense',
            'activeAdvances',
            'activeDeficitCount',
            'topCategory',
            'month',
            'year',
            'years',
            'isAllPeriod',
            'periodLabel'
        ));
    }

    public function create(Request $request)
    {
        return view('admin.expenses.form', [
            'expense' => new OperationalExpense([
                'expense_date' => now(),
                'payment_method' => 'transfer',
                'expense_type' => $request->get('expense_type', OperationalExpense::TYPE_CONVECTION),
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $merge = [];
        if ($request->has('amount') && $request->filled('amount')) {
            $cleaned = preg_replace('/[^\d]/', '', (string) $request->amount);
            $merge['amount'] = $cleaned === '' ? null : (int) $cleaned;
        }
        if (! $request->filled('expense_type')) {
            $merge['expense_type'] = OperationalExpense::TYPE_CONVECTION;
        }
        if (! empty($merge)) {
            $request->merge($merge);
        }

        $data = $request->validate([
            'expense_type' => ['required', Rule::in(array_keys(OperationalExpense::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(OperationalExpense::CATEGORIES))],
            'amount' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(OperationalExpense::METHODS))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'expense_type.required' => 'Jenis pengeluaran (Ops. Konveksi / Ops. Pribadi) wajib dipilih.',
            'title.required' => 'Keterangan pengeluaran wajib diisi.',
            'category.required' => 'Kategori beban wajib dipilih.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.min' => 'Nominal pengeluaran minimal Rp 1.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'receipt.max' => 'Ukuran file bukti/struk maksimal 5MB.',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('operational_expenses', 'local');
        }

        $expense = OperationalExpense::create([
            'reference_no' => OperationalExpense::generateReferenceNo(),
            'expense_type' => $data['expense_type'],
            'category' => $data['category'],
            'title' => $data['title'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
            'receipt_path' => $receiptPath,
            'recorded_by' => auth()->id(),
        ]);

        // Auto sync ke spreadsheet
        app(GoogleSheetService::class)->syncExpense($expense, 'add');

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Beban ' . $expense->type_label . ' ' . rupiah($expense->amount) . ' ("' . $expense->title . '") berhasil dicatat.');
    }

    public function edit(OperationalExpense $expense)
    {
        return view('admin.expenses.form', [
            'expense' => $expense,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, OperationalExpense $expense)
    {
        $merge = [];
        if ($request->has('amount') && $request->filled('amount')) {
            $cleaned = preg_replace('/[^\d]/', '', (string) $request->amount);
            $merge['amount'] = $cleaned === '' ? null : (int) $cleaned;
        }
        if (! $request->filled('expense_type')) {
            $merge['expense_type'] = $expense->expense_type ?? OperationalExpense::TYPE_CONVECTION;
        }
        if (! empty($merge)) {
            $request->merge($merge);
        }

        $data = $request->validate([
            'expense_type' => ['required', Rule::in(array_keys(OperationalExpense::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(OperationalExpense::CATEGORIES))],
            'amount' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(OperationalExpense::METHODS))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'expense_type.required' => 'Jenis pengeluaran (Ops. Konveksi / Ops. Pribadi) wajib dipilih.',
            'title.required' => 'Keterangan pengeluaran wajib diisi.',
            'category.required' => 'Kategori beban wajib dipilih.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.min' => 'Nominal pengeluaran minimal Rp 1.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'receipt.max' => 'Ukuran file bukti/struk maksimal 5MB.',
        ]);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path)) {
                Storage::disk('local')->delete($expense->receipt_path);
            }
            $expense->receipt_path = $request->file('receipt')->store('operational_expenses', 'local');
        }

        $expense->update([
            'expense_type' => $data['expense_type'],
            'category' => $data['category'],
            'title' => $data['title'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
            'receipt_path' => $expense->receipt_path,
        ]);

        // Auto sync ke spreadsheet
        app(GoogleSheetService::class)->syncExpense($expense, 'add');

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Beban operasional ' . $expense->reference_no . ' berhasil diperbarui.');
    }

    public function destroy(OperationalExpense $expense)
    {
        if ($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path)) {
            Storage::disk('local')->delete($expense->receipt_path);
        }

        $ref = $expense->reference_no;
        $title = $expense->title;
        $amount = $expense->amount;

        // Auto sync delete ke spreadsheet
        app(GoogleSheetService::class)->syncExpense($expense, 'delete');

        $expense->delete();

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Beban operasional ' . $ref . ' ("' . $title . '") sebesar ' . rupiah($amount) . ' berhasil dihapus.');
    }

    public function receipt(OperationalExpense $expense)
    {
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return response()->file(Storage::disk('local')->path($expense->receipt_path));
    }
}
