<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCost;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderCostController extends Controller
{
    public function store(Request $request, Order $order)
    {
        // Sanitize numeric inputs (strip thousand separators, commas to dots)
        $merge = [];
        if ($request->has('unit_price') && $request->filled('unit_price')) {
            $cleanedUnitPrice = preg_replace('/[^\d]/', '', (string) $request->unit_price);
            $merge['unit_price'] = $cleanedUnitPrice === '' ? null : (int) $cleanedUnitPrice;
        }

        if ($request->has('quantity') && $request->filled('quantity')) {
            // Replace Indonesian decimal comma with dot (e.g., "12,5" -> "12.5")
            $cleanedQty = str_replace(',', '.', trim((string) $request->quantity));
            $merge['quantity'] = is_numeric($cleanedQty) ? (float) $cleanedQty : $request->quantity;
        }

        if ($request->has('amount') && $request->filled('amount')) {
            $cleanedAmount = preg_replace('/[^\d]/', '', (string) $request->amount);
            $merge['amount'] = $cleanedAmount === '' ? null : (int) $cleanedAmount;
        } elseif (! empty($merge['quantity']) && ! empty($merge['unit_price'])) {
            // Otomatis hitung total biaya jika kuantitas dan harga satuan terisi
            $merge['amount'] = (int) round($merge['quantity'] * $merge['unit_price']);
        }

        // Jika harga satuan kosong tapi qty dan amount terisi, hitung harga satuannya
        if (empty($merge['unit_price']) && ! empty($merge['quantity']) && ! empty($merge['amount']) && $merge['quantity'] > 0) {
            $merge['unit_price'] = (int) round($merge['amount'] / $merge['quantity']);
        }

        if (! empty($merge)) {
            $request->merge($merge);
        }

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(OrderCost::CATEGORIES))],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'integer', 'min:1'],
            'spent_at' => ['required', 'date'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'amount.required' => 'Nominal total biaya wajib diisi.',
            'amount.integer' => 'Nominal biaya harus berupa angka bulat.',
            'amount.min' => 'Nominal biaya minimal Rp 1.',
            'unit_price.integer' => 'Harga satuan harus berupa angka bulat.',
            'category.required' => 'Kategori biaya wajib dipilih.',
            'description.required' => 'Keterangan pengeluaran wajib diisi.',
            'spent_at.required' => 'Tanggal pengeluaran wajib diisi.',
            'receipt.max' => 'Ukuran nota/bukti maksimal 5MB.',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('order_costs/'.$order->id, 'local');
        }

        $cost = $order->costs()->create([
            'category' => $data['category'],
            'description' => $data['description'],
            'quantity' => $data['quantity'] ?? null,
            'unit' => $data['unit'] ?? null,
            'unit_price' => $data['unit_price'] ?? null,
            'amount' => $data['amount'],
            'receipt_path' => $receiptPath,
            'spent_at' => $data['spent_at'],
            'recorded_by' => auth()->id(),
        ]);

        $order->logActivity(
            'Biaya '.$cost->category_label.' sebesar '.rupiah($cost->amount).' ("'.$cost->description.'") dicatat'
        );

        return redirect()->route('admin.orders.show', [$order, 'tab' => 'hpp'])
            ->with('success', 'Biaya produksi '.rupiah($cost->amount).' berhasil dicatat.');
    }

    public function destroy(Order $order, OrderCost $cost)
    {
        abort_if($cost->order_id !== $order->id, 404);

        if ($cost->receipt_path) {
            ImageUploader::delete($cost->receipt_path, 'local');
        }

        $amount = $cost->amount;
        $categoryLabel = $cost->category_label;
        $description = $cost->description;

        $cost->delete();

        $order->logActivity(
            'Biaya '.$categoryLabel.' sebesar '.rupiah($amount).' ("'.$description.'") dihapus'
        );

        return redirect()->route('admin.orders.show', [$order, 'tab' => 'hpp'])
            ->with('success', 'Catatan biaya produksi berhasil dihapus.');
    }

    public function receipt(OrderCost $cost)
    {
        abort_unless($cost->receipt_path && Storage::disk('local')->exists($cost->receipt_path), 404);

        return response()->file(Storage::disk('local')->path($cost->receipt_path));
    }
}
