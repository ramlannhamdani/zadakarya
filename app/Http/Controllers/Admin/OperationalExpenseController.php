<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OperationalExpense;
use App\Services\GoogleSheetService;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OperationalExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->month : (int) now()->format('n');
        $year = $request->filled('year') ? (int) $request->year : (int) now()->format('Y');

        $query = OperationalExpense::with('recorder');

        // Filter tanggal / bulan
        if ($request->get('period') !== 'all') {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            $query->whereBetween('expense_date', [$startDate, $endDate]);
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

        // Metrik Ringkasan
        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd = $now->copy()->endOfMonth();
        $thisYearStart = $now->copy()->startOfYear();
        $thisYearEnd = $now->copy()->endOfYear();

        $thisMonthTotal = (int) OperationalExpense::whereBetween('expense_date', [$thisMonthStart, $thisMonthEnd])->sum('amount');
        $thisYearTotal = (int) OperationalExpense::whereBetween('expense_date', [$thisYearStart, $thisYearEnd])->sum('amount');
        $allTimeTotal = (int) OperationalExpense::sum('amount');

        // Kategori beban terbesar bulan ini
        $topCategoryRow = OperationalExpense::whereBetween('expense_date', [$thisMonthStart, $thisMonthEnd])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $topCategory = null;
        if ($topCategoryRow) {
            $topCategory = [
                'name' => OperationalExpense::CATEGORIES[$topCategoryRow->category] ?? $topCategoryRow->category,
                'amount' => (int) $topCategoryRow->total,
            ];
        }

        // Daftar tahun untuk filter dropdown
        $earliestYear = OperationalExpense::min('expense_date');
        $startYear = $earliestYear ? (int) Carbon::parse($earliestYear)->format('Y') : (int) now()->format('Y');
        $years = range(max($startYear, now()->format('Y') - 3), now()->format('Y') + 1);
        rsort($years);

        return view('admin.expenses.index', compact(
            'expenses',
            'thisMonthTotal',
            'thisYearTotal',
            'allTimeTotal',
            'topCategory',
            'month',
            'year',
            'years'
        ));
    }

    public function create()
    {
        return view('admin.expenses.form', [
            'expense' => new OperationalExpense([
                'expense_date' => now(),
                'payment_method' => 'transfer',
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
        if (! empty($merge)) {
            $request->merge($merge);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(OperationalExpense::CATEGORIES))],
            'amount' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(OperationalExpense::METHODS))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
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
            ->with('success', 'Beban operasional ' . rupiah($expense->amount) . ' ("' . $expense->title . '") berhasil dicatat.');
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
        if (! empty($merge)) {
            $request->merge($merge);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(OperationalExpense::CATEGORIES))],
            'amount' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(OperationalExpense::METHODS))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
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
