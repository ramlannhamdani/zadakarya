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

<div class="space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Invoice</span>
        </a>
    </div>

    <form method="POST"
          action="{{ $invoice->exists ? route('admin.invoices.update', $invoice) : route('admin.invoices.store') }}"
          class="w-full space-y-6"
          x-data="{
              items: {{ \Illuminate\Support\Js::from($prefillItems) }},
              discount: {{ (int) old('discount', $invoice->discount ?? ($order?->discount ?? 0)) }},
              additional: {{ (int) old('additional_cost', $invoice->additional_cost ?? 0) }},
              addItem() { this.items.push({ description: '', quantity: 1, unit: 'pcs', unit_price: 0 }); },
              removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
              get subtotal() { return this.items.reduce((s, it) => s + (parseInt(it.quantity) || 0) * (parseInt(it.unit_price) || 0), 0); },
              get grand() { return Math.max(0, this.subtotal - (parseInt(this.discount) || 0) + (parseInt(this.additional) || 0)); },
              format(v) { return 'Rp ' + (v || 0).toLocaleString('id-ID'); }
          }">
        @csrf
        @if($invoice->exists) @method('PUT') @endif

        <!-- CARD 1: INFORMASI INVOICE -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $invoice->exists ? 'Edit Data Invoice' : 'Terbitkan Invoice Baru' }}</h3>
                    <p class="text-xs text-slate-400">Informasi pesanan tertaut, tanggal terbit, dan batas jatuh tempo penagihan</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="order_id">
                        Tautkan ke Pesanan <span class="text-rose-500">*</span>
                    </label>
                    <select class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                            id="order_id" name="order_id" required @unless($invoice->exists) onchange="if (this.value) window.location = '{{ route('admin.invoices.create') }}?order=' + this.value" @endunless>
                        <option value="">— Pilih pesanan —</option>
                        @foreach($orders as $o)
                            <option value="{{ $o->id }}" @selected(old('order_id', $invoice->order_id ?? $order?->id) == $o->id)>
                                {{ $o->order_number }} — {{ $o->customer->name }} — {{ $o->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="date">
                        Tanggal Terbit <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                           type="date" id="date" name="date" value="{{ old('date', $invoice->date?->toDateString() ?? now()->toDateString()) }}" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="due_date">
                        Batas Jatuh Tempo
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                           type="date" id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date?->toDateString()) }}">
                </div>
            </div>
        </div>

        <!-- CARD 2: ITEM INVOICE -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm font-bold shrink-0">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Rincian Tagihan Invoice</h3>
                        <p class="text-xs text-slate-400">Item deskripsi penagihan, kuantiti, dan nominal tagihan per baris</p>
                    </div>
                </div>
                <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span>Tambah Item</span>
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(item, i) in items" :key="i">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/50 p-4 transition-all hover:bg-slate-50">
                        <div class="grid gap-4 sm:grid-cols-12">
                            <div class="sm:col-span-12 md:col-span-5">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Deskripsi Item <span class="text-rose-500">*</span></label>
                                <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                                       type="text" :name="`items[${i}][description]`" x-model="item.description" required>
                            </div>
                            <div class="sm:col-span-3 md:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Qty</label>
                                <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                                       type="number" min="1" :name="`items[${i}][quantity]`" x-model="item.quantity" required>
                            </div>
                            <div class="sm:col-span-3 md:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Satuan</label>
                                <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                                       type="text" :name="`items[${i}][unit]`" x-model="item.unit">
                            </div>
                            <div class="sm:col-span-4 md:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Harga Satuan (Rp)</label>
                                <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                                       type="number" min="0" :name="`items[${i}][unit_price]`" x-model="item.unit_price" required>
                            </div>
                            <div class="flex items-end sm:col-span-2 md:col-span-1">
                                <button type="button" @click="removeItem(i)"
                                        class="p-2.5 rounded-xl border border-slate-200 text-rose-500 hover:bg-rose-50 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                                        :disabled="items.length === 1" title="Hapus">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- TOTALS & ADJUSTMENTS -->
            <div class="mt-6 grid gap-5 border-t border-slate-100 pt-6 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="discount">Potongan Diskon (Rp)</label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="number" min="0" id="discount" name="discount" x-model="discount">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="additional_cost_label">Biaya Tambahan (Label)</label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="additional_cost_label" name="additional_cost_label" value="{{ old('additional_cost_label', $invoice->additional_cost_label) }}" placeholder="Contoh: Ongkos kirim / Packing kayu">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="additional_cost">Biaya Tambahan (Rp)</label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="number" min="0" id="additional_cost" name="additional_cost" x-model="additional">
                </div>
            </div>

            <div class="mt-6 space-y-2 border-t border-slate-100 pt-5 text-right text-xs">
                <p class="text-slate-500">Subtotal Tagihan: <span class="inline-block w-36 font-mono font-bold text-slate-800" x-text="format(subtotal)"></span></p>
                <p class="text-rose-600">Diskon: <span class="inline-block w-36 font-mono font-bold text-rose-600" x-text="'- ' + format(parseInt(discount) || 0)"></span></p>
                <p class="text-slate-500">Biaya Tambahan: <span class="inline-block w-36 font-mono font-bold text-slate-800" x-text="'+ ' + format(parseInt(additional) || 0)"></span></p>
                <div class="mt-3 flex items-center justify-end gap-3 border-t border-slate-100 pt-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Grand Total Invoice</span>
                    <span class="text-2xl font-bold font-mono text-brand-700" x-text="format(grand)"></span>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-slate-100">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="invoice_notes">Catatan Pembayaran & Rekening</label>
                <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                          id="invoice_notes" name="notes" rows="3" placeholder="Contoh: DP 50% dibayar saat persetujuan, pelunasan sebelum pengiriman. Transfer ke BCA 12345678 a.n. Zada Karya Production.">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
            <p class="text-xs text-slate-400">Nomor invoice resmi di-generate secara otomatis oleh sistem.</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.invoices.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                    <i class="fa-solid fa-check text-[11px]"></i>
                    <span>{{ $invoice->exists ? 'Simpan Perubahan' : 'Buat Invoice' }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
