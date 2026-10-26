@extends('layouts.admin')

@section('title', $isEdit ? 'Edit Beban ' . $expense->reference_no : 'Catat Beban Operasional Baru')

@section('content')
<div class="max-w-3xl space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.expenses.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Beban Operasional</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
        <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                <i class="fa-solid {{ $isEdit ? 'fa-pen-to-square' : 'fa-receipt' }}"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $isEdit ? 'Edit Beban ' . $expense->reference_no : 'Catat Pengeluaran Beban Baru' }}</h3>
                <p class="text-xs text-slate-400">Pencatatan pengeluaran operasional konveksi atau prive pribadi untuk laporan laba rugi</p>
            </div>
        </div>

        <form method="POST"
              action="{{ $isEdit ? route('admin.expenses.update', $expense) : route('admin.expenses.store') }}"
              enctype="multipart/form-data"
              class="space-y-5"
              x-data="{
                  expenseType: '{{ old('expense_type', $expense->expense_type ?? 'convection') }}',
                  amount: '{{ old('amount', $expense->amount ?? '') }}',
                  formatRupiah(val) {
                      let num = val.toString().replace(/[^\d]/g, '');
                      return num ? parseInt(num).toLocaleString('id-ID') : '';
                  }
              }">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            {{-- Pilihan Jenis Beban (Ops. Konveksi vs Ops. Pribadi) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Jenis Pengeluaran Beban <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="relative flex cursor-pointer items-start rounded-2xl border p-4 transition-all"
                           :class="expenseType === 'convection' ? 'border-indigo-500 bg-indigo-50/50 shadow-xs' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/60'">
                        <input type="radio" name="expense_type" value="convection" x-model="expenseType" class="mt-1 text-indigo-600 focus:ring-indigo-500" required>
                        <div class="ml-3">
                            <span class="block text-xs font-bold text-indigo-950">🏭 Ops. Konveksi (Bisnis)</span>
                            <span class="mt-1 block text-[11px] text-slate-500 leading-relaxed">Beban operasional konveksi: listrik, workshop, gaji, servis mesin dll. Mengurangi laba bersih konveksi.</span>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer items-start rounded-2xl border p-4 transition-all"
                           :class="expenseType === 'personal' ? 'border-rose-500 bg-rose-50/50 shadow-xs' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/60'">
                        <input type="radio" name="expense_type" value="personal" x-model="expenseType" class="mt-1 text-rose-600 focus:ring-rose-500" required>
                        <div class="ml-3">
                            <span class="block text-xs font-bold text-rose-950">👤 Ops. Pribadi (Prive Owner)</span>
                            <span class="mt-1 block text-[11px] text-slate-500 leading-relaxed">Penarikan pribadi/keluarga dari kas. Mengurangi uang kas riil, bukan beban operasional konveksi.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="expense_date">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                    <input type="date" id="expense_date" name="expense_date"
                           value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="category">Kategori Beban <span class="text-rose-500">*</span></label>
                    <select id="category" name="category" class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors" required>
                        <option value="">— Pilih Kategori Beban —</option>
                        @foreach(\App\Models\OperationalExpense::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $expense->category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="title">Keterangan / Nama Pengeluaran <span class="text-rose-500">*</span></label>
                <input type="text" id="title" name="title"
                       value="{{ old('title', $expense->title) }}"
                       placeholder="Contoh: Token Listrik Workshop September, Sewa Ruko Semester 2"
                       class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors" required>
                <p class="mt-1 text-[11px] text-slate-400">Tuliskan tujuan pengeluaran dengan jelas agar mudah diaudit.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="amount">Nominal Beban (Rp) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-bold text-slate-400">Rp</span>
                        <input type="text" id="amount" name="amount"
                               x-model="amount"
                               @input="amount = formatRupiah($event.target.value)"
                               placeholder="0"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 pl-10 pr-3.5 py-2.5 text-xs font-bold font-mono text-slate-900 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="payment_method">Metode Pembayaran <span class="text-rose-500">*</span></label>
                    <select id="payment_method" name="payment_method" class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors" required>
                        @foreach(\App\Models\OperationalExpense::METHODS as $key => $label)
                            <option value="{{ $key }}" @selected(old('payment_method', $expense->payment_method ?? 'transfer') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="receipt">Bukti Struk / Kuitansi / Invoice (Opsional)</label>
                <input type="file" id="receipt" name="receipt" accept="image/*,application/pdf"
                       class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2 text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300">
                <p class="mt-1 text-[11px] text-slate-400">Format yang didukung: JPG, PNG, WEBP, PDF (Maks. 5 MB).</p>
                @if($expense->receipt_path)
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        <span class="text-slate-400">File tersimpan:</span>
                        <a href="{{ route('admin.expenses.receipt', $expense) }}" target="_blank" class="font-semibold text-brand-600 hover:underline">
                            Lihat Bukti Saat Ini
                        </a>
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="notes">Catatan Tambahan (Opsional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Informasi tambahan seperti nomor rekening tujuan, nama penerima, dll."
                          class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors">{{ old('notes', $expense->notes) }}</textarea>
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.expenses.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                    <i class="fa-solid fa-check text-[11px]"></i>
                    <span>{{ $isEdit ? 'Simpan Perubahan' : 'Catat Beban Operasional' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
