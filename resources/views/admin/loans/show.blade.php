@extends('layouts.admin')

@section('title', 'Detail Pinjaman ' . $loan->reference_no)

@section('content')
<div x-data="{
    editModal: false,
    payModal: false,
    payAmount: '{{ $loan->remaining_amount }}'
}">
    <div class="space-y-6">
    {{-- Back Bar & Action Buttons --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.loans.index') }}" class="btn-outline !py-1.5 !px-3 text-xs font-semibold flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>Kembali</span>
            </a>
            <div>
                <h1 class="text-xl font-extrabold text-ink flex items-center gap-2">
                    <span>{{ $loan->reference_no }}</span>
                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $loan->status_color }}">
                        {{ $loan->status_label }}
                    </span>
                </h1>
                <p class="text-xs text-neutral-500">Pemberi Pinjaman: <strong class="text-ink font-semibold">{{ $loan->lender_name }}</strong> · Diterima {{ $loan->loan_date->translatedFormat('d F Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="editModal = true" class="btn-outline !py-2 !px-3.5 text-xs font-semibold">
                Edit Pinjaman
            </button>

            @if($loan->status !== \App\Models\Loan::STATUS_PAID)
                <button type="button" @click="payModal = true" class="btn-primary !bg-emerald-600 hover:!bg-emerald-700 !py-2 !px-4 text-xs font-bold flex items-center gap-1.5 shadow-xs">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>Bayar / Cicil Hutang</span>
                </button>
            @endif

            <form method="POST" action="{{ route('admin.loans.destroy', $loan) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pinjaman ini beserta seluruh riwayat cicilannya?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100 transition" title="Hapus Pinjaman">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    {{-- Main Grid: Overview & Repayment History --}}
    <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Left Column: Loan Summary & Linked Order --}}
        <div class="space-y-5">
            {{-- Card: Summary Info --}}
            <div class="admin-card">
                <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500 border-b border-line pb-2.5">
                    Informasi Pinjaman
                </h3>

                <div class="mt-4 space-y-3.5 text-sm">
                    <div>
                        <span class="text-xs text-neutral-400 block">Pemberi Pinjaman</span>
                        <span class="font-extrabold text-base text-ink">{{ $loan->lender_name }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-line">
                        <div>
                            <span class="text-xs text-neutral-400 block">Total Pokok Pinjam</span>
                            <span class="font-bold text-ink text-base">{{ rupiah($loan->amount) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-neutral-400 block">Sisa Hutang</span>
                            <span class="font-extrabold text-base {{ $loan->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ rupiah($loan->remaining_amount) }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-line">
                        <div class="flex items-center justify-between text-xs text-neutral-500 mb-1">
                            <span>Progress Pengembalian:</span>
                            <span class="font-bold text-ink">{{ $loan->repaid_percentage }}% ({{ rupiah($loan->amount_paid) }})</span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-neutral-200 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: {{ $loan->repaid_percentage }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-line text-xs">
                        <div>
                            <span class="text-neutral-400 block">Tanggal Diterima:</span>
                            <span class="font-semibold text-neutral-700">{{ $loan->loan_date->translatedFormat('d F Y') }}</span>
                        </div>
                        <div>
                            <span class="text-neutral-400 block">Jatuh Tempo:</span>
                            <span class="font-semibold {{ $loan->is_overdue ? 'text-rose-600 font-bold' : 'text-neutral-700' }}">
                                {{ $loan->due_date ? $loan->due_date->translatedFormat('d F Y') : '—' }}
                                @if($loan->is_overdue)
                                    <span class="block text-[10px] text-rose-600 font-bold uppercase">Terlambat!</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    @if($loan->purpose)
                        <div class="pt-2 border-t border-line">
                            <span class="text-xs text-neutral-400 block">Keperluan / Keterangan:</span>
                            <p class="font-medium text-neutral-700 mt-0.5 text-xs">{{ $loan->purpose }}</p>
                        </div>
                    @endif

                    @if($loan->notes)
                        <div class="pt-2 border-t border-line">
                            <span class="text-xs text-neutral-400 block">Catatan Tambahan:</span>
                            <p class="font-normal text-neutral-600 mt-0.5 text-xs whitespace-pre-line">{{ $loan->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card: Linked Order (Terkait Piutang Pesanan) --}}
            @if($loan->order)
                <div class="admin-card border-l-4 !border-l-indigo-600 bg-indigo-50/10">
                    <div class="flex items-center justify-between border-b border-line pb-2.5">
                        <div class="flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-indigo-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-900">
                                Ditautkan ke Pesanan
                            </h3>
                        </div>
                        <a href="{{ route('admin.orders.show', $loan->order) }}" class="text-xs font-bold text-indigo-700 hover:underline">
                            Lihat Order &rarr;
                        </a>
                    </div>

                    <div class="mt-3 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Nomor Pesanan:</span>
                            <span class="font-mono font-bold text-ink">{{ $loan->order->order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Nama Customer:</span>
                            <span class="font-semibold text-ink">{{ $loan->order->customer->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Total Nilai Order:</span>
                            <span class="font-bold text-ink">{{ rupiah($loan->order->grand_total) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Status Pembayaran:</span>
                            <span class="font-semibold {{ $loan->order->remaining > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ $loan->order->payment_status_label }}
                            </span>
                        </div>
                        <div class="flex justify-between pt-1.5 border-t border-indigo-200 font-bold">
                            <span class="text-indigo-900">Sisa Piutang Customer:</span>
                            <span class="{{ $loan->order->remaining > 0 ? 'text-rose-600 font-extrabold text-sm' : 'text-emerald-700' }}">
                                {{ rupiah($loan->order->remaining) }}
                            </span>
                        </div>
                    </div>

                    @if($loan->order->remaining <= 0 && $loan->status !== \App\Models\Loan::STATUS_PAID)
                        <div class="mt-3 rounded-lg bg-emerald-100/70 p-2.5 text-xs text-emerald-800 border border-emerald-200 flex items-start gap-2">
                            <span class="text-base">💡</span>
                            <div>
                                <p class="font-bold">Piutang pesanan ini sudah Lunas!</p>
                                <p class="text-[11px] mt-0.5">Customer sudah melunasi seluruh pembayaran. Anda dapat segera mengembalikan pinjaman ini kepada pemberi pinjaman.</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Right Column: Repayment History Table & Quick Payment --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="admin-card !p-0 overflow-hidden">
                <div class="flex items-center justify-between p-4 sm:p-5 border-b border-line bg-cream/40">
                    <div>
                        <h3 class="text-sm font-extrabold text-ink">Riwayat Pengembalian / Cicilan</h3>
                        <p class="text-xs text-neutral-500">Daftar pembayaran yang sudah diserahkan ke {{ $loan->lender_name }}</p>
                    </div>
                    @if($loan->status !== \App\Models\Loan::STATUS_PAID)
                        <button type="button" @click="payModal = true" class="btn-primary !bg-emerald-600 hover:!bg-emerald-700 !py-1.5 !px-3 text-xs font-bold shadow-xs">
                            + Tambah Cicilan
                        </button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                                <th class="px-5 py-3">No. Ref &amp; Tanggal</th>
                                <th class="px-5 py-3 text-right">Nominal Bayar</th>
                                <th class="px-5 py-3">Metode Bayar</th>
                                <th class="px-5 py-3">Catatan</th>
                                <th class="px-5 py-3 text-center">Bukti</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($loan->payments as $payment)
                                <tr class="hover:bg-cream/30 transition">
                                    <td class="px-5 py-3.5">
                                        <span class="font-mono font-bold text-ink">{{ $payment->reference_no ?: 'LPAY-'.$payment->id }}</span>
                                        <p class="text-xs text-neutral-500">{{ $payment->payment_date->translatedFormat('d M Y') }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-extrabold text-emerald-700">
                                        {{ rupiah($payment->amount) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-neutral-700 text-xs font-medium">
                                        {{ $payment->method_label }}
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-neutral-600">
                                        {{ $payment->notes ?: '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-xs">
                                        @if($payment->attachment_path)
                                            <a href="{{ asset('storage/' . $payment->attachment_path) }}" target="_blank"
                                               class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700 hover:bg-indigo-100">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                                Lihat
                                            </a>
                                        @else
                                            <span class="text-neutral-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <form method="POST" action="{{ route('admin.loans.payments.destroy', [$loan, $payment]) }}"
                                              onsubmit="return confirm('Hapus riwayat cicilan ini? Sisa hutang akan kembali bertambah.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-semibold" title="Hapus Riwayat Cicilan">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-neutral-500">
                                        <p class="font-semibold">Belum ada riwayat pengembalian / cicilan.</p>
                                        <p class="text-xs text-neutral-400 mt-1">Saat Anda mengembalikan dana sebagian atau penuh, catat cicilan dengan tombol di atas.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-line bg-cream/40 font-bold">
                                <td class="px-5 py-3 text-xs uppercase tracking-wider text-neutral-600">Total Pengembalian:</td>
                                <td class="px-5 py-3 text-right text-emerald-700">{{ rupiah($loan->amount_paid) }}</td>
                                <td colspan="4" class="px-5 py-3 text-xs text-neutral-500">Sisa Hutang: <strong class="text-rose-600 font-extrabold">{{ rupiah($loan->remaining_amount) }}</strong></td>
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
                 class="fixed inset-0 bg-neutral-900/60 transition-opacity backdrop-blur-xs"></div>

            <div x-show="editModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-lg transform rounded-2xl bg-white p-6 text-left shadow-2xl transition-all">

                <div class="flex items-center justify-between border-b border-line pb-3.5">
                    <h3 class="text-base font-extrabold text-ink">Edit Data Pinjaman</h3>
                    <button type="button" @click="editModal = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.loans.update', $loan) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Nama Pemberi Pinjaman <span class="text-red-500">*</span></label>
                        <input type="text" name="lender_name" value="{{ old('lender_name', $loan->lender_name) }}" required class="form-input mt-1 w-full text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Total Pinjaman (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" value="{{ old('amount', $loan->amount) }}" required min="{{ $loan->amount_paid }}" step="1000" class="form-input mt-1 w-full text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Tanggal Diterima <span class="text-red-500">*</span></label>
                            <input type="date" name="loan_date" value="{{ old('loan_date', $loan->loan_date->toDateString()) }}" required class="form-input mt-1 w-full text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Target Jatuh Tempo</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $loan->due_date?->toDateString()) }}" class="form-input mt-1 w-full text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Keperluan</label>
                        <input type="text" name="purpose" value="{{ old('purpose', $loan->purpose) }}" class="form-input mt-1 w-full text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Catatan</label>
                        <textarea name="notes" rows="2" class="form-input mt-1 w-full text-sm">{{ old('notes', $loan->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-line">
                        <button type="button" @click="editModal = false" class="btn-outline !py-2 !px-4 text-xs font-semibold">Batal</button>
                        <button type="submit" class="btn-primary !py-2 !px-5 text-xs font-bold">Simpan Perubahan</button>
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
                 class="fixed inset-0 bg-neutral-900/60 transition-opacity backdrop-blur-xs"></div>

            <div x-show="payModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-md transform rounded-2xl bg-white p-6 text-left shadow-2xl transition-all">

                <div class="flex items-center justify-between border-b border-line pb-3.5">
                    <div>
                        <h3 class="text-base font-extrabold text-ink">Catat Cicilan / Pelunasan</h3>
                        <p class="text-xs text-neutral-500">Pemberi: {{ $loan->lender_name }}</p>
                    </div>
                    <button type="button" @click="payModal = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.loans.payments.store', $loan) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf

                    <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Sisa Hutang Saat Ini</p>
                            <p class="text-lg font-extrabold text-rose-700">{{ rupiah($loan->remaining_amount) }}</p>
                        </div>
                        <button type="button"
                                @click="payAmount = '{{ $loan->remaining_amount }}'"
                                class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700 shadow-xs transition">
                            Lunasi Penuh
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Nominal yang Dibayar (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" required min="1000" max="{{ $loan->remaining_amount }}"
                               x-model="payAmount"
                               class="form-input mt-1 w-full text-base font-extrabold text-ink">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Tanggal Bayar <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" required value="{{ now()->toDateString() }}" class="form-input mt-1 w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Metode Bayar <span class="text-red-500">*</span></label>
                            <select name="payment_method" class="form-input mt-1 w-full text-xs font-medium">
                                <option value="transfer">Transfer Bank</option>
                                <option value="cash">Tunai / Kas</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Catatan / Keterangan</label>
                        <input type="text" name="notes" placeholder="Contoh: Transfer via BCA..." class="form-input mt-1 w-full text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Bukti Transfer (Opsional)</label>
                        <input type="file" name="attachment" accept="image/*,application/pdf"
                               class="form-input mt-1 w-full text-xs file:mr-2 file:rounded-md file:border-0 file:bg-cream file:px-2 file:py-1 file:text-xs file:font-semibold">
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-line">
                        <button type="button" @click="payModal = false" class="btn-outline !py-2 !px-4 text-xs font-semibold">Batal</button>
                        <button type="submit" class="btn-primary !bg-emerald-600 hover:!bg-emerald-700 !py-2 !px-5 text-xs font-bold shadow-xs">
                            Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
