<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['order.customer', 'invoice'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->whereHas('order', fn ($o) => $o->where('order_number', 'like', $term));
            })
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'type' => ['nullable', Rule::in(array_keys(Payment::TYPES))],
            'payment_date' => ['required', 'date'],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:1000'],
            'invoice_id' => ['nullable', Rule::exists('invoices', 'id')->where('order_id', $order->id)],
            'proof' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payments/'.$order->id, 'local');
        }

        // Tanpa pilihan eksplisit, pembayaran pertama sebuah pesanan dianggap DP
        // dan sisanya pelunasan — sama seperti alur kerja di lapangan.
        $type = $data['type'] ?? ($order->payments()->exists() ? Payment::TYPE_SETTLEMENT : Payment::TYPE_DP);

        $payment = $order->payments()->create([
            'invoice_id' => $data['invoice_id'] ?? null,
            'amount' => $data['amount'],
            'type' => $type,
            'payment_date' => $data['payment_date'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
            'proof_path' => $proofPath,
            'recorded_by' => auth()->id(),
        ]);

        $order->refreshPaymentStatus();
        $order->logActivity($payment->type_label.' '.rupiah($data['amount']).' dicatat — status: '.$order->payment_status_label);

        app(\App\Services\GoogleSheetService::class)->syncPayment($payment, 'add');

        return back()->with('success', 'Pembayaran dicatat. Status: '.$order->payment_status_label.'.');
    }

    /**
     * Tautkan (atau lepas) pembayaran ke salah satu invoice pesanan yang sama.
     * Dipakai pada pesanan dengan beberapa invoice supaya angka "Terbayar" di
     * tiap invoice tidak tercampur.
     */
    public function linkInvoice(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'invoice_id' => ['nullable', Rule::exists('invoices', 'id')->where('order_id', $payment->order_id)],
        ]);

        $payment->update(['invoice_id' => $data['invoice_id'] ?: null]);

        $label = $payment->invoice_id
            ? 'Pembayaran '.rupiah($payment->amount).' ditautkan ke invoice '.$payment->invoice->invoice_number.'.'
            : 'Tautan invoice pada pembayaran '.rupiah($payment->amount).' dilepas.';

        $payment->order->logActivity($label);

        return back()->with('success', $label);
    }

    public function proof(Payment $payment)
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return response()->file(Storage::disk('local')->path($payment->proof_path));
    }

    public function destroy(Payment $payment)
    {
        $order = $payment->order;

        if ($payment->proof_path && Storage::disk('local')->exists($payment->proof_path)) {
            Storage::disk('local')->delete($payment->proof_path);
        }

        $amount = $payment->amount;

        app(\App\Services\GoogleSheetService::class)->syncPayment($payment, 'delete');

        $payment->delete();
        $order->refreshPaymentStatus();
        $order->logActivity('Pembayaran '.rupiah($amount).' dihapus — status: '.$order->payment_status_label);

        return back()->with('success', 'Pembayaran dihapus.');
    }
}
