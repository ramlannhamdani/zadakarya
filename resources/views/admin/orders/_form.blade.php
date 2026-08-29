@php
    $oldItems = old('items', $order?->items?->map(fn ($i) => [
        'product_name' => $i->product_name,
        'description' => $i->description,
        'quantity' => $i->quantity,
        'unit' => $i->unit,
        'unit_price' => $i->unit_price,
    ])->values()->all() ?: [['product_name' => '', 'description' => '', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 0]]);
@endphp

<div x-data="{
        items: {{ \Illuminate\Support\Js::from($oldItems) }},
        addItem() { this.items.push({ product_name: '', description: '', quantity: 1, unit: 'pcs', unit_price: 0 }); },
        removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
        get total() { return this.items.reduce((sum, it) => sum + (parseInt(it.quantity) || 0) * (parseInt(it.unit_price) || 0), 0); },
        format(v) { return 'Rp ' + (v || 0).toLocaleString('id-ID'); }
    }">

    <div class="admin-card">
        <h2 class="font-extrabold text-ink">Informasi Pesanan</h2>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="customer_id">Customer <span class="text-brand-600">*</span></label>
                <select class="form-input" id="customer_id" name="customer_id" required>
                    <option value="">— Pilih customer —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', $order->customer_id ?? ($selectedCustomer ?? null)) == $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-neutral-500">Belum ada? <a href="{{ route('admin.customers.create') }}" class="font-semibold text-brand-600 hover:underline">Buat customer baru</a></p>
            </div>
            <div>
                <label class="form-label" for="name">Nama Proyek / Pesanan <span class="text-brand-600">*</span></label>
                <input class="form-input" type="text" id="name" name="name" value="{{ old('name', $order->name ?? '') }}" placeholder="Contoh: Seragam Polo PT ABC 2026" required>
            </div>
            <div>
                <label class="form-label" for="deadline">Deadline (internal)</label>
                <input class="form-input" type="date" id="deadline" name="deadline" value="{{ old('deadline', $order?->deadline?->toDateString()) }}">
            </div>
            <div>
                <label class="form-label" for="estimated_completion">Estimasi Selesai (tampil di tracking)</label>
                <input class="form-input" type="date" id="estimated_completion" name="estimated_completion" value="{{ old('estimated_completion', $order?->estimated_completion?->toDateString()) }}">
            </div>
            <div>
                <label class="form-label" for="dp_amount">Nominal DP (Rp)</label>
                <input class="form-input" type="number" id="dp_amount" name="dp_amount" min="0" value="{{ old('dp_amount', $order->dp_amount ?? '') }}" placeholder="Contoh: 5000000">
                <p class="mt-1 text-xs text-neutral-500">DP tidak mengurangi grand total — DP dicatat sebagai pembayaran dan mengurangi <em>sisa tagihan</em>.</p>
            </div>
            @unless($order)
            <div class="rounded-lg bg-cream p-4 sm:col-span-2" x-data="{ dp: {{ old('record_dp') ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="create_invoice" value="0">
                    <input type="checkbox" name="create_invoice" value="1" @checked(old('create_invoice', true)) class="rounded border-line text-brand-600 focus:ring-brand-600">
                    Buat invoice otomatis dari item pesanan ini (nomor invoice = nomor pesanan)
                </label>
                <label class="mt-3 flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="record_dp" value="0">
                    <input type="checkbox" name="record_dp" value="1" x-model="dp" class="rounded border-line text-brand-600 focus:ring-brand-600">
                    DP sudah diterima — catat sebagai pembayaran sekarang
                </label>
                <div x-show="dp" x-cloak class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="dp_date">Tanggal DP Diterima</label>
                        <input class="form-input" type="date" id="dp_date" name="dp_date" value="{{ old('dp_date', now()->toDateString()) }}">
                    </div>
                    <div>
                        <label class="form-label" for="dp_method">Metode</label>
                        <select class="form-input" id="dp_method" name="dp_method">
                            @foreach(\App\Models\Payment::METHODS as $key => $label)
                                <option value="{{ $key }}" @selected(old('dp_method') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="mt-2 text-xs text-neutral-500">Nominal yang dicatat = Nominal DP di atas. Pesanan langsung berstatus <strong>DP</strong> dan sisa tagihan berkurang.</p>
            </div>
            @endunless
            <div class="sm:col-span-2">
                <label class="form-label" for="notes">Catatan Internal</label>
                <textarea class="form-input" id="notes" name="notes" rows="2">{{ old('notes', $order->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="admin-card mt-5">
        <div class="flex items-center justify-between">
            <h2 class="font-extrabold text-ink">Item Produk</h2>
            <button type="button" @click="addItem()" class="btn-outline !px-4 !py-2 text-xs">+ Tambah Item</button>
        </div>

        <template x-for="(item, i) in items" :key="i">
            <div class="mt-4 rounded-lg border border-line p-4">
                <div class="grid gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-6 md:col-span-3 xl:col-span-4">
                        <label class="form-label">Nama Produk <span class="text-brand-600">*</span></label>
                        <input class="form-input" type="text" :name="`items[${i}][product_name]`" x-model="item.product_name" placeholder="Contoh: Polo Shirt Lacoste" required>
                    </div>
                    <div class="sm:col-span-6 md:col-span-3">
                        <label class="form-label">Keterangan</label>
                        <input class="form-input" type="text" :name="`items[${i}][description]`" x-model="item.description" placeholder="Warna, bahan, detail...">
                    </div>
                    <div class="sm:col-span-4 md:col-span-2 xl:col-span-1">
                        <label class="form-label">Qty <span class="text-brand-600">*</span></label>
                        <input class="form-input" type="number" min="1" :name="`items[${i}][quantity]`" x-model="item.quantity" required>
                    </div>
                    <div class="sm:col-span-4 md:col-span-2">
                        <label class="form-label">Satuan</label>
                        <input class="form-input" type="text" :name="`items[${i}][unit]`" x-model="item.unit">
                    </div>
                    <div class="sm:col-span-4 md:col-span-2">
                        <label class="form-label">Harga Satuan <span class="text-brand-600">*</span></label>
                        <input class="form-input" type="number" min="0" :name="`items[${i}][unit_price]`" x-model="item.unit_price" required>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3">
                    <button type="button" @click="removeItem(i)" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-500 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40" :disabled="items.length === 1" title="Hapus item">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Hapus item
                    </button>
                    <p class="text-right text-sm font-semibold text-neutral-600">
                        Subtotal: <span x-text="format((parseInt(item.quantity) || 0) * (parseInt(item.unit_price) || 0))"></span>
                    </p>
                </div>
            </div>
        </template>

        <div class="mt-4 flex items-center justify-end gap-3 border-t border-line pt-4">
            <span class="text-sm font-bold uppercase tracking-wider text-neutral-500">Grand Total</span>
            <span class="text-2xl font-extrabold text-brand-600" x-text="format(total)"></span>
        </div>
    </div>
</div>
