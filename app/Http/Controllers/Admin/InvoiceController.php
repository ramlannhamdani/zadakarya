<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Support\Sequence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with(['order.customer'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('invoice_number', 'like', $term)
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', $term)));
            })
            ->latest('date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $order = null;
        if ($request->filled('order')) {
            $order = Order::with(['customer', 'items'])->find($request->order);
        }

        return view('admin.invoices.form', [
            'invoice' => new Invoice(['date' => now()->toDateString()]),
            'order' => $order,
            'orders' => Order::with('customer')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $invoice = DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'invoice_number' => Sequence::invoiceNumber(),
                'order_id' => $data['order_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'additional_cost_label' => $data['additional_cost_label'] ?? null,
                'additional_cost' => $data['additional_cost'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($invoice, $data['items']);

            $invoice->order->logActivity('Invoice '.$invoice->invoice_number.' dibuat');

            return $invoice;
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice '.$invoice->invoice_number.' berhasil dibuat.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['order.customer', 'order.payments', 'items']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['order.customer', 'items']);

        return view('admin.invoices.form', [
            'invoice' => $invoice,
            'order' => $invoice->order,
            'orders' => Order::with('customer')->latest()->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'order_id' => $data['order_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'additional_cost_label' => $data['additional_cost_label'] ?? null,
                'additional_cost' => $data['additional_cost'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            $this->syncItems($invoice, $data['items']);

            $invoice->order->logActivity('Invoice '.$invoice->invoice_number.' diperbarui');
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice diperbarui.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['order.customer', 'order.payments', 'items']);

        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function destroy(Invoice $invoice)
    {
        $order = $invoice->order;
        $number = $invoice->invoice_number;
        $invoice->delete();
        $order->logActivity('Invoice '.$number.' dihapus');

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice dihapus.');
    }

    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $i => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit' => $item['unit'] ?: 'pcs',
                'unit_price' => (int) $item['unit_price'],
                'total' => (int) $item['quantity'] * (int) $item['unit_price'],
                'sort_order' => $i,
            ]);
        }

        $invoice->refreshTotals();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'additional_cost_label' => ['nullable', 'string', 'max:150'],
            'additional_cost' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ], [
            'items.required' => 'Minimal satu item invoice harus diisi.',
        ]);
    }
}
