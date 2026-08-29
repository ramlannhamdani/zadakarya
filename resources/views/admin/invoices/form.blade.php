@extends('layouts.admin')

@section('title', $invoice->exists ? 'Edit Invoice '.$invoice->invoice_number : 'Buat Invoice')

@section('content')
@php
    $prefillItems = old('items');
    if (! $prefillItems && $invoice->exists) {
        $prefillItems = $invoice->items->map(fn ($i) => [
            'description' => $i->description,
            'quantity' => $i->quantity,
            'unit' => $i->unit,
            'unit_price' => $i->unit_price,
        ])->values()->all();
    }
    if (! $prefillItems && $order) {
        $prefillItems = $order->items->map(fn ($i) => [
            'description' => trim($i->product_name.($i->description ? ' — '.$i->description : '')),
            'quantity' => $i->quantity,
            'unit' => $i->unit,
            'unit_price' => $i->unit_price,
        ])->values()->all();
    }
    $prefillItems = $prefillItems ?: [['description' => '', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 0]];
@endphp

<form method="POST"
      action="{{ $invoice->exists ? route('admin.invoices.update', $invoice) : route('admin.invoices.store') }}"
      class="max-w-4xl"
      x-data="{
          items: {{ \Illuminate\Support\Js::from($prefillItems) }},
          discount: {{ (int) old('discount', $invoice->discount ?? 0) }},
          additional: {{ (int) old('additional_cost', $invoice->additional_cost ?? 0) }},
          addItem() { this.items.push({ description: '', quantity: 1, unit: 'pcs', unit_price: 0 }); },
          removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
          get subtotal() { return this.items.reduce((s, it) => s + (parseInt(it.quantity) || 0) * (parseInt(it.unit_price) || 0), 0); },
          get grand() { return Math.max(0, this.subtotal - (parseInt(this.discount) || 0) + (parseInt(this.additional) || 0)); },
          format(v) { return 'Rp ' + (v || 0).toLocaleString('id-ID'); }
      }">
    @csrf
    @if($invoice->exists) @method('PUT') @endif

    <div class="admin-card">
        <h2 class="font-extrabold text-ink">Informasi Invoice</h2>
        <div class="mt-4 grid gap-5 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="form-label" for="order_id">Pesanan <span class="text-brand-600">*</span></label>
                <select class="form-input" id="order_id" name="order_id" required @unless($invoice->exists) onchange="if (this.value) window.location = '{{ route('admin.invoices.create') }}?order=' + this.value" @endunless>
                    <option value="">— Pilih pesanan —</option>
                    @foreach($orders as $o)
                        <option value="{{ $o->id }}" @selected(old('order_id', $invoice->order_id ?? $order?->id) == $o->id)>
                            {{ $o->order_number }} — {{ $o->customer->name }} — {{ $o->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="date">Tanggal <span class="text-brand-600">*</span></label>
                <input class="form-input" type="date" id="date" name="date" value="{{ old('date', $invoice->date?->toDateString() ?? now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="form-label" for="due_date">Jatuh Tempo</label>
                <input class="form-input" type="date" id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date?->toDateString()) }}">
            </div>
        </div>
    </div>

    <div class="admin-card mt-5">
        <div class="flex items-center justify-between">
            <h2 class="font-extrabold text-ink">Item Invoice</h2>
            <button type="button" @click="addItem()" class="btn-outline !px-4 !py-2 text-xs">+ Tambah Item</button>
        </div>

        <template x-for="(item, i) in items" :key="i">
            <div class="mt-4 grid gap-4 rounded-lg border border-line p-4 sm:grid-cols-12">
                <div class="sm:col-span-12 md:col-span-5">
                    <label class="form-label">Deskripsi <span class="text-brand-600">*</span></label>
                    <input class="form-input" type="text" :name="`items[${i}][description]`" x-model="item.description" required>
                </div>
                <div class="sm:col-span-3 md:col-span-2">
                    <label class="form-label">Qty</label>
                    <input class="form-input" type="number" min="1" :name="`items[${i}][quantity]`" x-model="item.quantity" required>
                </div>
                <div class="sm:col-span-3 md:col-span-2">
                    <label class="form-label">Satuan</label>
                    <input class="form-input" type="text" :name="`items[${i}][unit]`" x-model="item.unit">
                </div>
                <div class="sm:col-span-4 md:col-span-2">
                    <label class="form-label">Harga Satuan</label>
                    <input class="form-input" type="number" min="0" :name="`items[${i}][unit_price]`" x-model="item.unit_price" required>
                </div>
                <div class="flex items-end sm:col-span-2 md:col-span-1">
                    <button type="button" @click="removeItem(i)" class="rounded-lg border border-red-200 p-2.5 text-red-500 hover:bg-red-50" :disabled="items.length === 1" title="Hapus">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </template>

        <div class="mt-5 grid gap-5 border-t border-line pt-5 sm:grid-cols-3">
            <div>
                <label class="form-label" for="discount">Diskon (Rp)</label>
                <input class="form-input" type="number" min="0" id="discount" name="discount" x-model="discount">
            </div>
            <div>
                <label class="form-label" for="additional_cost_label">Biaya Tambahan (label)</label>
                <input class="form-input" type="text" id="additional_cost_label" name="additional_cost_label" value="{{ old('additional_cost_label', $invoice->additional_cost_label) }}" placeholder="Contoh: Ongkos kirim">
            </div>
            <div>
                <label class="form-label" for="additional_cost">Biaya Tambahan (Rp)</label>
                <input class="form-input" type="number" min="0" id="additional_cost" name="additional_cost" x-model="additional">
            </div>
        </div>

        <div class="mt-5 space-y-1.5 border-t border-line pt-4 text-right text-sm">
            <p>Subtotal: <span class="inline-block w-36 font-semibold" x-text="format(subtotal)"></span></p>
            <p>Diskon: <span class="inline-block w-36 font-semibold text-red-500" x-text="'- ' + format(parseInt(discount) || 0)"></span></p>
            <p>Biaya Tambahan: <span class="inline-block w-36 font-semibold" x-text="'+ ' + format(parseInt(additional) || 0)"></span></p>
            <p class="text-base">Grand Total: <span class="inline-block w-36 text-xl font-extrabold text-brand-600" x-text="format(grand)"></span></p>
        </div>

        <div class="mt-4">
            <label class="form-label" for="invoice_notes">Catatan Invoice</label>
            <textarea class="form-input" id="invoice_notes" name="notes" rows="3" placeholder="Contoh: DP 50% dibayar saat persetujuan, pelunasan sebelum pengiriman.">{{ old('notes', $invoice->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" class="btn-primary">{{ $invoice->exists ? 'Simpan Perubahan' : 'Buat Invoice' }}</button>
        <a href="{{ route('admin.invoices.index') }}" class="btn-outline">Batal</a>
        @unless($invoice->exists)<p class="text-sm text-neutral-500">Nomor invoice (INV-xxxx) dibuat otomatis.</p>@endunless
    </div>
</form>
@endsection
