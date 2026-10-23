@extends('layouts.admin')

@section('title', $isEdit ? 'Edit Beban ' . $expense->reference_no : 'Catat Beban Operasional Baru')

@section('content')
<div class="mb-5 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.expenses.index') }}" class="btn-outline !p-2 text-neutral-600 hover:text-ink" title="Kembali">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <h2 class="text-xl font-extrabold text-ink">{{ $isEdit ? 'Edit Beban ' . $expense->reference_no : 'Catat Beban Operasional Baru' }}</h2>
    </div>
</div>

<form method="POST"
      action="{{ $isEdit ? route('admin.expenses.update', $expense) : route('admin.expenses.store') }}"
      enctype="multipart/form-data"
      class="max-w-3xl"
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

    <div class="admin-card space-y-5">
        {{-- Pilihan Jenis Beban (Ops. Konveksi vs Ops. Pribadi) --}}
        <div>
            <label class="form-label mb-2 block">Jenis Pengeluaran Beban <span class="text-brand-600">*</span></label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="relative flex cursor-pointer items-start rounded-xl border p-3.5 shadow-sm transition hover:border-indigo-400"
                       :class="expenseType === 'convection' ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-line bg-white'">
                    <input type="radio" name="expense_type" value="convection" x-model="expenseType" class="mt-1 text-indigo-600 focus:ring-indigo-500" required>
                    <div class="ml-3">
                        <span class="block text-sm font-extrabold text-indigo-900">🏭 Ops. Konveksi (Bisnis)</span>
                        <span class="mt-0.5 block text-xs text-neutral-500 leading-relaxed">Beban operasional konveksi: listrik, workshop, gaji, servis mesin dll. Mengurangi laba bersih konveksi.</span>
                    </div>
                </label>

                <label class="relative flex cursor-pointer items-start rounded-xl border p-3.5 shadow-sm transition hover:border-rose-400"
                       :class="expenseType === 'personal' ? 'border-rose-600 bg-rose-50/50 ring-2 ring-rose-500/20' : 'border-line bg-white'">
                    <input type="radio" name="expense_type" value="personal" x-model="expenseType" class="mt-1 text-rose-600 focus:ring-rose-500" required>
                    <div class="ml-3">
                        <span class="block text-sm font-extrabold text-rose-900">👤 Ops. Pribadi (Prive Owner)</span>
                        <span class="mt-0.5 block text-xs text-neutral-500 leading-relaxed">Penarikan pribadi/keluarga dari kas. Mengurangi uang kas riil, bukan beban operasional konveksi.</span>
                    </div>
                </label>
            </div>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="expense_date">Tanggal Pengeluaran <span class="text-brand-600">*</span></label>
                <input type="date" id="expense_date" name="expense_date"
                       value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                       class="form-input" required>
            </div>

            <div>
                <label class="form-label" for="category">Kategori Beban <span class="text-brand-600">*</span></label>
                <select id="category" name="category" class="form-input" required>
                    <option value="">— Pilih Kategori Beban —</option>
                    @foreach(\App\Models\OperationalExpense::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $expense->category) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="form-label" for="title">Keterangan / Nama Pengeluaran <span class="text-brand-600">*</span></label>
            <input type="text" id="title" name="title"
                   value="{{ old('title', $expense->title) }}"
                   placeholder="Contoh: Token Listrik Workshop September, Sewa Ruko Semester 2"
                   class="form-input" required>
            <p class="mt-1 text-xs text-neutral-500">Tuliskan tujuan pengeluaran dengan jelas agar mudah diaudit.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="amount">Nominal Beban (Rp) <span class="text-brand-600">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-bold text-neutral-500">Rp</span>
                    <input type="text" id="amount" name="amount"
                           x-model="amount"
                           @input="amount = formatRupiah($event.target.value)"
                           placeholder="0"
                           class="form-input pl-10 font-bold text-ink" required>
                </div>
            </div>

            <div>
                <label class="form-label" for="payment_method">Metode Pembayaran <span class="text-brand-600">*</span></label>
                <select id="payment_method" name="payment_method" class="form-input" required>
                    @foreach(\App\Models\OperationalExpense::METHODS as $key => $label)
                        <option value="{{ $key }}" @selected(old('payment_method', $expense->payment_method ?? 'transfer') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="form-label" for="receipt">Bukti Struk / Kuitansi / Invoice (Opsional)</label>
            <input type="file" id="receipt" name="receipt" accept="image/*,application/pdf" class="form-input text-sm">
            <p class="mt-1 text-xs text-neutral-500">Format yang didukung: JPG, PNG, WEBP, PDF (Maks. 5 MB).</p>
            @if($expense->receipt_path)
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="text-neutral-500">File tersimpan:</span>
                    <a href="{{ route('admin.expenses.receipt', $expense) }}" target="_blank" class="font-semibold text-brand-600 hover:underline">
                        Lihat Bukti Saat Ini
                    </a>
                </div>
            @endif
        </div>

        <div>
            <label class="form-label" for="notes">Catatan Tambahan (Opsional)</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Informasi tambahan seperti nomor rekening tujuan, nama penerima, dll." class="form-input">{{ old('notes', $expense->notes) }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-line">
            <a href="{{ route('admin.expenses.index') }}" class="btn-outline !px-4 !py-2.5 text-sm">Batal</a>
            <button type="submit" class="btn-primary !px-5 !py-2.5 text-sm font-bold">
                {{ $isEdit ? 'Simpan Perubahan' : 'Catat Beban Operasional' }}
            </button>
        </div>
    </div>
</form>
@endsection
