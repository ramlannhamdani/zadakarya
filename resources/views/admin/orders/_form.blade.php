@php
    $oldItems = old('items', $order?->items?->map(fn ($i) => [
        'product_name' => $i->product_name,
        'description' => $i->description,
        'quantity' => $i->quantity,
        'unit' => $i->unit,
        'unit_price' => $i->unit_price,
    ])->values()->all() ?: [['product_name' => '', 'description' => '', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 0]]);
@endphp

<div class="space-y-6" x-data="{
        items: {{ \Illuminate\Support\Js::from($oldItems) }},
        discount: {{ (int) old('discount', $order?->discount ?? 0) }},
        addItem() { this.items.push({ product_name: '', description: '', quantity: 1, unit: 'pcs', unit_price: 0 }); },
        removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
        get subtotal() { return this.items.reduce((sum, it) => sum + (parseInt(it.quantity) || 0) * (parseInt(it.unit_price) || 0), 0); },
        get total() { return Math.max(0, this.subtotal - (parseInt(this.discount) || 0)); },
        format(v) { return 'Rp ' + (v || 0).toLocaleString('id-ID'); }
    }">

    <!-- CARD 1: INFORMASI PESANAN -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
        <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                <i class="fa-solid fa-shirt"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Informasi Dasar Pesanan</h3>
                <p class="text-xs text-slate-400">Data customer, nama project seragam/pakaian, dan estimasi pengerjaan</p>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="customer_id">
                    Customer / Pemesan <span class="text-rose-500">*</span>
                </label>
                <select class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                        id="customer_id" name="customer_id" required>
                    <option value="">— Pilih customer —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', $order->customer_id ?? ($selectedCustomer ?? null)) == $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-[11px] text-slate-400">Belum ada di daftar? <a href="{{ route('admin.customers.create') }}" class="font-semibold text-brand-600 hover:underline">Tambah customer baru &rarr;</a></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                    Nama Proyek / Pesanan <span class="text-rose-500">*</span>
                </label>
                <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                       type="text" id="name" name="name" value="{{ old('name', $order->name ?? '') }}" placeholder="Contoh: Seragam Polo PT ABC 2026" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="deadline">
                    Deadline Internal Produksi
                </label>
                <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                       type="date" id="deadline" name="deadline" value="{{ old('deadline', $order?->deadline?->toDateString()) }}">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="estimated_completion">
                    Estimasi Selesai (Tampil di Tracking Publik)
                </label>
                <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                       type="date" id="estimated_completion" name="estimated_completion" value="{{ old('estimated_completion', $order?->estimated_completion?->toDateString()) }}">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="dp_amount">
                    Rencana / Nominal DP (Rp)
                </label>
                <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                       type="number" id="dp_amount" name="dp_amount" min="0" value="{{ old('dp_amount', $order->dp_amount ?? '') }}" placeholder="Contoh: 5000000">
                <p class="mt-1 text-[11px] text-slate-400">DP tidak mengurangi grand total pesanan, melainkan dicatat sebagai pembayaran pelunasan awal.</p>
            </div>

            @unless($order)
            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 sm:col-span-2 space-y-3" x-data="{ dp: {{ old('record_dp') ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                    <input type="hidden" name="create_invoice" value="0">
                    <input type="checkbox" name="create_invoice" value="1" @checked(old('create_invoice', true)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    <span>Buat invoice otomatis dari item pesanan ini (nomor invoice = nomor pesanan)</span>
                </label>

                <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                    <input type="hidden" name="record_dp" value="0">
                    <input type="checkbox" name="record_dp" value="1" x-model="dp" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    <span>DP sudah diterima — langsung catat sebagai pembayaran sekarang</span>
                </label>

                <div x-show="dp" x-cloak class="mt-3 grid gap-4 sm:grid-cols-2 pt-3 border-t border-slate-200/60">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="dp_date">Tanggal DP Diterima</label>
                        <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs text-slate-800"
                               type="date" id="dp_date" name="dp_date" value="{{ old('dp_date', now()->toDateString()) }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="dp_method">Metode Pembayaran DP</label>
                        <select class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs text-slate-800" id="dp_method" name="dp_method">
                            @foreach(\App\Models\Payment::METHODS as $key => $label)
                                <option value="{{ $key }}" @selected(old('dp_method') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            @endunless

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="notes">
                    Catatan Internal Pesanan
                </label>
                <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                          id="notes" name="notes" rows="2" placeholder="Catatan internal tim produksi / penagihan...">{{ old('notes', $order->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <!-- CARD 2: ITEM PRODUK -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Rincian Item Produk</h3>
                    <p class="text-xs text-slate-400">Daftar pakaian/produk yang diproduksi berserta harga satuan dan kuantiti</p>
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
                        <div class="sm:col-span-6 md:col-span-3 xl:col-span-4">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Produk <span class="text-rose-500">*</span></label>
                            <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                                   type="text" :name="`items[${i}][product_name]`" x-model="item.product_name" placeholder="Contoh: Polo Shirt Lacoste" required>
                        </div>
                        <div class="sm:col-span-6 md:col-span-3">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Keterangan / Spesifikasi</label>
                            <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                                   type="text" :name="`items[${i}][description]`" x-model="item.description" placeholder="Warna, bahan, bordir...">
                        </div>
                        <div class="sm:col-span-4 md:col-span-2 xl:col-span-1">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Qty <span class="text-rose-500">*</span></label>
                            <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                                   type="number" min="1" :name="`items[${i}][quantity]`" x-model="item.quantity" required>
                        </div>
                        <div class="sm:col-span-4 md:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Satuan</label>
                            <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                                   type="text" :name="`items[${i}][unit]`" x-model="item.unit">
                        </div>
                        <div class="sm:col-span-4 md:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Harga Satuan (Rp) <span class="text-rose-500">*</span></label>
                            <input class="w-full rounded-xl bg-white border border-slate-200/80 px-3 py-2 text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                                   type="number" min="0" :name="`items[${i}][unit_price]`" x-model="item.unit_price" required>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-3 pt-3 border-t border-slate-200/60">
                        <button type="button" @click="removeItem(i)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                                :disabled="items.length === 1">
                            <i class="fa-regular fa-trash-can text-xs"></i>
                            <span>Hapus Item</span>
                        </button>
                        <p class="text-right text-xs text-slate-500">
                            Subtotal: <span class="font-bold font-mono text-slate-900 ml-1 text-sm" x-text="format((parseInt(item.quantity) || 0) * (parseInt(item.unit_price) || 0))"></span>
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <!-- TOTALS & DISCOUNT -->
        <div class="mt-6 grid gap-5 border-t border-slate-100 pt-6 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="discount">Potongan Diskon (Rp)</label>
                <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                       type="number" min="0" id="discount" name="discount" x-model="discount" placeholder="0">
                <p class="mt-1 text-[11px] text-slate-400">Diskon memotong Subtotal dan langsung menentukan Grand Total akhir.</p>
            </div>
            <div class="space-y-2 pt-2 text-right sm:col-span-1 lg:col-span-2">
                <p class="text-xs text-slate-500">Subtotal Item: <span class="inline-block w-40 font-bold font-mono text-slate-800" x-text="format(subtotal)"></span></p>
                <p class="text-xs text-rose-600">Diskon: <span class="inline-block w-40 font-bold font-mono text-rose-600" x-text="'- ' + format(parseInt(discount) || 0)"></span></p>
                <div class="mt-2 flex items-center justify-end gap-3 border-t border-slate-100 pt-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Grand Total</span>
                    <span class="text-2xl font-bold font-mono text-brand-700" x-text="format(total)"></span>
                </div>
            </div>
        </div>
    </div>
</div>
