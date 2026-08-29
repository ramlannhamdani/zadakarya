<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $orders = Order::with(['customer', 'items'])
            ->when(in_array($status, ['active', 'completed', 'cancelled']), fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('order_number', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)->orWhere('company', 'like', $term)));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status'));
    }

    public function create(Request $request)
    {
        return view('admin.orders.create', [
            'customers' => Customer::orderBy('name')->get(),
            'selectedCustomer' => $request->query('customer'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $order = DB::transaction(function () use ($data) {
            $order = Order::create([
                'order_number' => Sequence::orderNumber(),
                'customer_id' => $data['customer_id'],
                'name' => $data['name'],
                'dp_amount' => $data['dp_amount'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'estimated_completion' => $data['estimated_completion'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $i => $item) {
                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => (int) $item['quantity'],
                    'unit' => $item['unit'] ?: 'pcs',
                    'unit_price' => (int) $item['unit_price'],
                    'total' => (int) $item['quantity'] * (int) $item['unit_price'],
                    'sort_order' => $i,
                ]);
            }

            $order->createInitialStages();
            $order->refreshTotals();
            $order->logActivity('Pesanan dibuat dengan nomor '.$order->order_number);

            // Invoice otomatis dari item yang sama (bisa dimatikan lewat centang di form).
            $invoice = null;
            if ($data['create_invoice']) {
                $invoice = $order->invoices()->create([
                    'invoice_number' => Invoice::nextNumberFor($order),
                    'date' => now()->toDateString(),
                    'due_date' => $data['deadline'] ?? null,
                ]);
                foreach ($order->items as $i => $item) {
                    $invoice->items()->create([
                        'description' => trim($item->product_name.($item->description ? ' — '.$item->description : '')),
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                        'sort_order' => $i,
                    ]);
                }
                $invoice->refreshTotals();
                $order->logActivity('Invoice '.$invoice->invoice_number.' dibuat otomatis dari pesanan');
            }

            // DP yang sudah diterima langsung dicatat sebagai pembayaran (mengurangi sisa, bukan total).
            if ($data['record_dp'] && ! empty($data['dp_amount'])) {
                $order->payments()->create([
                    'invoice_id' => $invoice?->id,
                    'amount' => (int) $data['dp_amount'],
                    'payment_date' => $data['dp_date'] ?? now()->toDateString(),
                    'method' => $data['dp_method'] ?? 'transfer',
                    'note' => 'DP',
                    'recorded_by' => auth()->id(),
                ]);
                $order->refreshPaymentStatus();
                $order->logActivity('DP '.rupiah((int) $data['dp_amount']).' dicatat saat pesanan dibuat — status: '.$order->payment_status_label);
            }

            return $order;
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pesanan '.$order->order_number.' berhasil dibuat.');
    }

    public function show(Request $request, Order $order)
    {
        $order->load([
            'customer', 'items', 'stages.updater', 'activities.user',
            'attachments', 'productionPhotos', 'invoices', 'payments.invoice',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'tab' => $request->query('tab', 'overview'),
        ]);
    }

    public function edit(Order $order)
    {
        $order->load('items');

        return view('admin.orders.edit', [
            'order' => $order,
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($order, $data) {
            $order->update([
                'customer_id' => $data['customer_id'],
                'name' => $data['name'],
                'dp_amount' => $data['dp_amount'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'estimated_completion' => $data['estimated_completion'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->delete();
            foreach ($data['items'] as $i => $item) {
                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => (int) $item['quantity'],
                    'unit' => $item['unit'] ?: 'pcs',
                    'unit_price' => (int) $item['unit_price'],
                    'total' => (int) $item['quantity'] * (int) $item['unit_price'],
                    'sort_order' => $i,
                ]);
            }

            $order->refreshTotals();
            $order->logActivity('Data pesanan diperbarui');
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pesanan diperbarui.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
        ]);

        $order->update(['status' => $data['status']]);
        $order->logActivity('Status pesanan diubah menjadi '.$order->status_label);

        return back()->with('success', 'Status pesanan diperbarui.');
    }

    public function updateNotes(Request $request, Order $order)
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:10000']]);

        $order->update(['notes' => $data['notes']]);

        return back()->with('success', 'Catatan internal disimpan.');
    }

    public function destroy(Order $order)
    {
        if ($order->payments()->exists() || $order->invoices()->exists()) {
            return back()->with('error', 'Pesanan dengan invoice atau pembayaran tidak dapat dihapus. Gunakan status Dibatalkan.');
        }

        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Pesanan dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:200'],
            'dp_amount' => ['nullable', 'integer', 'min:0'],
            'create_invoice' => ['nullable', 'boolean'],
            'record_dp' => ['nullable', 'boolean'],
            'dp_date' => ['nullable', 'date'],
            'dp_method' => ['nullable', Rule::in(array_keys(Payment::METHODS))],
            'deadline' => ['nullable', 'date'],
            'estimated_completion' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:200'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ], [
            'items.required' => 'Minimal satu item produk harus diisi.',
        ]);

        // Default: invoice dibuat otomatis kecuali admin mematikan centangnya.
        $data['create_invoice'] = $request->has('create_invoice') ? $request->boolean('create_invoice') : true;
        $data['record_dp'] = $request->boolean('record_dp');

        return $data;
    }
}
