@extends('layouts.admin')

@section('title', 'Detail Pinjaman ' . $loan->reference_no)

@section('content')
<div x-data="{
    editModal: false,
    payModal: false,
    payAmount: '{{ $loan->remaining_amount }}'
}">
    <div class="space-y-6">
        <!-- Header Back Bar & Action Buttons -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.loans.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Daftar Pinjaman</span>
                </a>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="editModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                    <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                    <span>Edit Pinjaman</span>
                </button>

                @if($loan->status !== \App\Models\Loan::STATUS_PAID)
                    <button type="button" @click="payModal = true"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs active:scale-95 transition-all">
                        <i class="fa-solid fa-money-bill-transfer text-[11px]"></i>
                        <span>Bayar / Cicil Hutang</span>
                    </button>
                @endif

                <form method="POST" action="{{ route('admin.loans.destroy', $loan) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pinjaman ini beserta seluruh riwayat cicilannya?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 transition-colors" title="Hapus Pinjaman">
                        <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- MAIN GRID: OVERVIEW & REPAYMENT HISTORY -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT COLUMN: LOAN SUMMARY & LINKED ORDER -->
            <div class="space-y-6">
                <!-- CARD: INFORMASI PINJAMAN -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nomor Referensi</span>
                            <h2 class="font-bold font-mono text-slate-900 text-base mt-0.5">{{ $loan->reference_no }}</h2>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $loan->status_color }}">
                            {{ $loan->status_label }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-4 text-xs">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Pemberi Pinjaman / Kreditur</span>
                            <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $loan->lender_name }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100">
                            <div>
                                <span class="text-[11px] text-slate-400 block">Total Pokok Pinjam</span>
                                <span class="font-bold font-mono text-slate-900 text-sm mt-0.5 block">{{ rupiah($loan->amount) }}</span>
                            </div>
                            <div>
                                <span class="text-[11px] text-slate-400 block">Sisa Hutang</span>
                                <span class="font-bold font-mono text-sm mt-0.5 block {{ $loan->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ rupiah($loan->remaining_amount) }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1.5">
                                <span>Progress Pelunasan:</span>
                                <span class="font-bold text-slate-800">{{ $loan->repaid_percentage }}% ({{ rupiah($loan->amount_paid) }})</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: {{ $loan->repaid_percentage }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs">
                            <div>
                                <span class="text-[11px] text-slate-400 block">Tanggal Diterima:</span>
                                <span class="font-medium text-slate-700 mt-0.5 block">{{ $loan->loan_date->translatedFormat('d F Y') }}</span>
                            </div>
                            <div>
                                <span class="text-[11px] text-slate-400 block">Jatuh Tempo:</span>
                                <span class="font-semibold mt-0.5 block {{ $loan->is_overdue ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                    {{ $loan->due_date ? $loan->due_date->translatedFormat('d F Y') : '—' }}
                                    @if($loan->is_overdue)
                                        <span class="block text-[10px] text-rose-600 font-bold uppercase">Terlambat!</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        @if($loan->purpose)
                            <div class="pt-3 border-t border-slate-100">
                                <span class="text-[11px] text-slate-400 block">Keperluan / Keterangan:</span>
                                <p class="font-medium text-slate-700 mt-1 leading-relaxed">{{ $loan->purpose }}</p>
                            </div>
                        @endif

                        @if($loan->notes)
                            <div class="pt-3 border-t border-slate-100">
                                <span class="text-[11px] text-slate-400 block">Catatan Tambahan:</span>
                                <p class="text-slate-600 mt-1 whitespace-pre-line leading-relaxed">{{ $loan->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- CARD: DITAUTKAN KE PESANAN -->
                @if($loan->order)
                    <div class="bg-indigo-50/40 rounded-2xl p-6 shadow-sm border border-indigo-100">
                        <div class="flex items-center justify-between pb-3 border-b border-indigo-100">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-link text-indigo-700 text-xs"></i>
                                <h3 class="text-xs font-bold text-indigo-950 uppercase tracking-wider">
                                    Ditautkan ke Pesanan
                                </h3>
                            </div>
                            <a href="{{ route('admin.orders.show', $loan->order) }}" class="text-xs font-bold text-indigo-700 hover:underline">
                                Buka Pesanan &rarr;
                            </a>
                        </div>

                        <div class="mt-4 space-y-2.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nomor Pesanan:</span>
                                <span class="font-mono font-bold text-slate-800">{{ $loan->order->order_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nama Customer:</span>
                                <span class="font-semibold text-slate-800">{{ $loan->order->customer->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total Nilai Order:</span>
                                <span class="font-bold font-mono text-slate-800">{{ rupiah($loan->order->grand_total) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Status Pembayaran:</span>
                                <span class="font-semibold {{ $loan->order->remaining > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    {{ $loan->order->payment_status_label }}
                                </span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-indigo-200/60 font-bold">
                                <span class="text-indigo-950">Sisa Piutang Customer:</span>
                                <span class="font-mono {{ $loan->order->remaining > 0 ? 'text-rose-600 font-bold text-sm' : 'text-emerald-700' }}">
                                    {{ rupiah($loan->order->remaining) }}
                                </span>
                            </div>
                        </div>

                        @if($loan->order->remaining <= 0 && $loan->status !== \App\Models\Loan::STATUS_PAID)
                            <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-xs text-emerald-800 border border-emerald-100 flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-emerald-600 text-sm mt-0.5"></i>
                                <div>
                                    <p class="font-bold">Piutang pesanan ini sudah LUNAS!</p>
                                    <p class="text-[11px] mt-0.5 text-emerald-700">Customer sudah melunasi seluruh pembayaran. Anda dapat segera mengembalikan pinjaman ini kepada kreditur.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- RIGHT COLUMN: REPAYMENT HISTORY TABLE -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="flex items-center justify-between p-6 pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight">Riwayat Pengembalian / Cicilan</h3>
                            <p class="text-xs text-slate-400">Daftar pembayaran yang telah diserahkan kepada {{ $loan->lender_name }}</p>
                        </div>
                        @if($loan->status !== \App\Models\Loan::STATUS_PAID)
                            <button type="button" @click="payModal = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs active:scale-95 transition-all">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                <span>Tambah Cicilan</span>
                            </button>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead>
                                <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                    <th class="py-3 px-5 font-normal uppercase tracking-wider">No. Ref &amp; Tanggal</th>
                                    <th class="py-3 px-5 font-normal uppercase tracking-wider text-right">Nominal Bayar</th>
                                    <th class="py-3 px-5 font-normal uppercase tracking-wider">Metode Bayar</th>
                                    <th class="py-3 px-5 font-normal uppercase tracking-wider">Catatan</th>
                                    <th class="py-3 px-5 font-normal uppercase tracking-wider text-center">Bukti</th>
                                    <th class="py-3 px-5 font-normal uppercase tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($loan->payments as $payment)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3.5 px-5 font-mono">
                                            <span class="font-bold text-slate-800">{{ $payment->reference_no ?: 'LPAY-'.$payment->id }}</span>
                                            <p class="text-[11px] text-slate-400">{{ $payment->payment_date->translatedFormat('d M Y') }}</p>
                                        </td>
                                        <td class="py-3.5 px-5 text-right font-bold font-mono text-emerald-600 text-sm">
                                            {{ rupiah($payment->amount) }}
                                        </td>
                                        <td class="py-3.5 px-5 text-slate-700 font-medium">
                                            {{ $payment->method_label }}
                                        </td>
                                        <td class="py-3.5 px-5 text-slate-600 max-w-xs truncate">
                                            {{ $payment->notes ?: '—' }}
                                        </td>
                                        <td class="py-3.5 px-5 text-center">
                                            @if($payment->attachment_path)
                                                <a href="{{ asset('storage/' . $payment->attachment_path) }}" target="_blank"
                                                   class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-colors">
                                                    <i class="fa-solid fa-file-image text-[10px]"></i>
                                                    <span>Lihat</span>
                                                </a>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-5 text-center">
                                            <form method="POST" action="{{ route('admin.loans.payments.destroy', [$loan, $payment]) }}"
                                                  onsubmit="return confirm('Hapus riwayat cicilan ini? Sisa hutang akan kembali bertambah.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors" title="Hapus Riwayat Cicilan">
                                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-slate-400">
                                            <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                            Belum ada riwayat pengembalian / cicilan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-slate-100 bg-slate-50/50 font-bold">
                                    <td class="py-3.5 px-5 text-[11px] uppercase tracking-wider text-slate-500">Total Pengembalian:</td>
                                    <td class="py-3.5 px-5 text-right font-mono text-emerald-600 text-sm">{{ rupiah($loan->amount_paid) }}</td>
                                    <td colspan="4" class="py-3.5 px-5 text-xs text-slate-500">Sisa Hutang: <strong class="text-rose-600 font-mono font-bold">{{ rupiah($loan->remaining_amount) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: EDIT PINJAMAN --}}
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="editModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="editModal = false"
                 class="fixed inset-0 bg-slate-900/40 transition-opacity backdrop-blur-xs"></div>

            <div x-show="editModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-lg transform rounded-2xl bg-white p-6 sm:p-7 text-left shadow-2xl border border-slate-100 transition-all">

                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm font-bold shrink-0">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 tracking-tight">Edit Data Pinjaman</h3>
                            <p class="text-xs text-slate-400">{{ $loan->reference_no }} — {{ $loan->lender_name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="editModal = false"
                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.loans.update', $loan) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemberi Pinjaman <span class="text-rose-500">*</span></label>
                        <input type="text" name="lender_name" value="{{ old('lender_name', $loan->lender_name) }}" required
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Total Pinjaman (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="amount" value="{{ old('amount', $loan->amount) }}" required min="{{ $loan->amount_paid }}" step="1000"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Diterima <span class="text-rose-500">*</span></label>
                            <input type="date" name="loan_date" value="{{ old('loan_date', $loan->loan_date->toDateString()) }}" required
                                   class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Jatuh Tempo</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $loan->due_date?->toDateString()) }}"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keperluan / Keterangan</label>
                        <input type="text" name="purpose" value="{{ old('purpose', $loan->purpose) }}"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Tambahan</label>
                        <textarea name="notes" rows="2"
                                  class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">{{ old('notes', $loan->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="editModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">Batal</button>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                            <i class="fa-solid fa-check text-[11px]"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: BAYAR / CICIL PINJAMAN --}}
    <div x-show="payModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="payModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="payModal = false"
                 class="fixed inset-0 bg-slate-900/40 transition-opacity backdrop-blur-xs"></div>

            <div x-show="payModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-md transform rounded-2xl bg-white p-6 sm:p-7 text-left shadow-2xl border border-slate-100 transition-all">

                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm font-bold shrink-0">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 tracking-tight">Catat Cicilan / Pelunasan</h3>
                            <p class="text-xs text-slate-400">Pemberi: {{ $loan->lender_name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="payModal = false"
                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.loans.payments.store', $loan) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-3.5 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Sisa Hutang Saat Ini</p>
                            <p class="text-lg font-bold font-mono text-rose-700 mt-0.5">{{ rupiah($loan->remaining_amount) }}</p>
                        </div>
                        <button type="button"
                                @click="payAmount = '{{ $loan->remaining_amount }}'"
                                class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 shadow-xs active:scale-95 transition-all">
                            Lunasi Penuh
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nominal yang Dibayar (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="amount" required min="1000" max="{{ $loan->remaining_amount }}"
                               x-model="payAmount"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-sm font-bold font-mono text-slate-900 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Bayar <span class="text-rose-500">*</span></label>
                            <input type="date" name="payment_date" required value="{{ now()->toDateString() }}"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Metode Bayar <span class="text-rose-500">*</span></label>
                            <select name="payment_method" class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                                <option value="transfer">Transfer Bank</option>
                                <option value="cash">Tunai / Kas</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan / Keterangan</label>
                        <input type="text" name="notes" placeholder="Contoh: Transfer via BCA..."
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bukti Transfer (Opsional)</label>
                        <input type="file" name="attachment" accept="image/*,application/pdf"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2 text-xs text-slate-700 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="payModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">Batal</button>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                            <i class="fa-solid fa-check text-[11px]"></i>
                            <span>Simpan Pembayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
