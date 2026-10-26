<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('q');

        $query = Loan::with(['order.customer', 'recorder']);

        if ($status !== 'all' && in_array($status, [Loan::STATUS_UNPAID, Loan::STATUS_PARTIAL, Loan::STATUS_PAID])) {
            $query->where('status', $status);
        } elseif ($status === 'active') {
            $query->whereIn('status', [Loan::STATUS_UNPAID, Loan::STATUS_PARTIAL]);
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('reference_no', 'like', $term)
                  ->orWhere('lender_name', 'like', $term)
                  ->orWhere('purpose', 'like', $term)
                  ->orWhereHas('order', function ($oq) use ($term) {
                      $oq->where('order_number', 'like', $term)
                        ->orWhereHas('customer', function ($cq) use ($term) {
                            $cq->where('name', 'like', $term);
                        });
                  });
            });
        }

        // Hitung metrik KPI
        $allLoans = Loan::all();
        $totalActiveDebt = $allLoans->whereIn('status', [Loan::STATUS_UNPAID, Loan::STATUS_PARTIAL])->sum(fn ($l) => $l->remaining_amount);
        $totalDebtRecorded = $allLoans->sum('amount');
        $totalRepaidAllTime = $allLoans->sum('amount_paid');

        $now = now();
        $loansThisMonth = Loan::whereYear('loan_date', $now->year)
            ->whereMonth('loan_date', $now->month)
            ->sum('amount');

        $repaymentsThisMonth = LoanPayment::whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('amount');

        $dueSoonCount = Loan::whereIn('status', [Loan::STATUS_UNPAID, Loan::STATUS_PARTIAL])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $now->copy()->addDays(7))
            ->count();

        $loans = (clone $query)
            ->orderByRaw("CASE WHEN status != 'paid' THEN 0 ELSE 1 END")
            ->latest('loan_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Daftar pesanan dengan piutang aktif untuk dropdown pilihan talangan
        $ordersWithReceivables = Order::where('status', 'active')
            ->whereColumn('amount_paid', '<', 'grand_total')
            ->with('customer')
            ->latest('created_at')
            ->get();

        return view('admin.loans.index', compact(
            'loans',
            'status',
            'search',
            'totalActiveDebt',
            'totalDebtRecorded',
            'totalRepaidAllTime',
            'loansThisMonth',
            'repaymentsThisMonth',
            'dueSoonCount',
            'ordersWithReceivables'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lender_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1000',
            'loan_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:loan_date',
            'order_id' => 'nullable|exists:orders,id',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'lender_name.required' => 'Nama pemberi pinjaman wajib diisi.',
            'amount.required' => 'Nominal pinjaman wajib diisi.',
            'amount.min' => 'Nominal pinjaman minimal Rp 1.000.',
            'loan_date.required' => 'Tanggal pinjaman wajib diisi.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal pinjaman.',
        ]);

        $validated['reference_no'] = Loan::generateReferenceNo();
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = Loan::STATUS_UNPAID;
        $validated['amount_paid'] = 0;

        $loan = Loan::create($validated);

        return redirect()->route('admin.loans.index')
            ->with('success', 'Pinjaman ' . $loan->reference_no . ' dari ' . $loan->lender_name . ' berhasil dicatat.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['order.customer', 'payments.recorder', 'recorder']);

        return view('admin.loans.show', compact('loan'));
    }

    public function update(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'lender_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:' . $loan->amount_paid,
            'loan_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:loan_date',
            'order_id' => 'nullable|exists:orders,id',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'amount.min' => 'Nominal pinjaman tidak boleh lebih kecil dari yang sudah dibayarkan (Rp ' . number_format($loan->amount_paid, 0, ',', '.') . ').',
        ]);

        $loan->update($validated);
        $loan->recalculateStatus();

        return redirect()->back()->with('success', 'Data pinjaman ' . $loan->reference_no . ' berhasil diperbarui.');
    }

    public function destroy(Loan $loan)
    {
        $ref = $loan->reference_no;

        // Hapus file attachment pembayaran jika ada
        foreach ($loan->payments as $payment) {
            if ($payment->attachment_path && Storage::disk('public')->exists($payment->attachment_path)) {
                Storage::disk('public')->delete($payment->attachment_path);
            }
        }

        $loan->delete();

        return redirect()->route('admin.loans.index')
            ->with('success', 'Pinjaman ' . $ref . ' berhasil dihapus.');
    }

    public function storePayment(Request $request, Loan $loan)
    {
        $remaining = $loan->remaining_amount;

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000|max:' . $remaining,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:transfer,cash,other',
            'notes' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ], [
            'amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa hutang (Rp ' . number_format($remaining, 0, ',', '.') . ').',
            'amount.min' => 'Nominal pembayaran minimal Rp 1.000.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('loan-payments', 'public');
        }

        $payment = $loan->payments()->create([
            'reference_no' => LoanPayment::generateReferenceNo(),
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
            'attachment_path' => $attachmentPath,
            'recorded_by' => auth()->id(),
        ]);

        $loan->refresh();

        $msg = 'Pembayaran pinjaman sebesar Rp ' . number_format($payment->amount, 0, ',', '.') . ' berhasil dicatat.';
        if ($loan->status === Loan::STATUS_PAID) {
            $msg .= ' 🎉 Selamat, pinjaman ini telah LUNAS!';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroyPayment(Loan $loan, LoanPayment $payment)
    {
        if ($payment->attachment_path && Storage::disk('public')->exists($payment->attachment_path)) {
            Storage::disk('public')->delete($payment->attachment_path);
        }

        $payment->delete();
        $loan->refresh();

        return redirect()->back()->with('success', 'Riwayat pembayaran pinjaman berhasil dihapus.');
    }
}
