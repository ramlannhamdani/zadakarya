@extends('layouts.admin')

@section('title', 'Pinjaman & Dana Talangan')

@section('content')
<div x-data="{
    createModal: false,
    payModal: false,
    activeLoan: null,
    payAmount: '',
    openPayModal(loan) {
        this.activeLoan = loan;
        this.payAmount = loan.remaining_amount;
        this.payModal = true;
    }
}">

    <!-- 1. SUMMARY METRICS TILES (Sleek Flat Container) -->
    <section class="bg-white rounded-2xl p-6 shadow-sm">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
            <!-- Total Sisa Hutang Aktif -->
            <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Sisa Hutang Aktif</p>
                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">Kewajiban</span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-rose-600">{{ rupiah($totalActiveDebt) }}</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Total pinjaman: {{ rupiah($totalDebtRecorded) }}</p>
            </div>

            <!-- Pinjaman Masuk Bulan Ini -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Pinjaman Masuk</p>
                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700">Bulan Ini</span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-indigo-700">{{ rupiah($loansThisMonth) }}</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Dana talangan diterima</p>
            </div>

            <!-- Pelunasan Bulan Ini -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Pelunasan</p>
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Bulan Ini</span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ rupiah($repaymentsThisMonth) }}</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Total terbayar: {{ rupiah($totalRepaidAllTime) }}</p>
            </div>

            <!-- Jatuh Tempo Mendekat -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Jatuh Tempo</p>
                    <span class="rounded-full {{ $dueSoonCount > 0 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' }} px-2 py-0.5 text-[10px] font-bold">≤ 7 Hari</span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold {{ $dueSoonCount > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $dueSoonCount }}</span>
                    <span class="text-[11px] text-slate-400 font-medium">pinjaman</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Target pelunasan segera</p>
            </div>
        </div>
    </section>

    <!-- 2. FILTER & ACTION TOOLBAR -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Status Filter Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs">
                <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $status === 'all' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Semua
                </a>
                <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'active'])) }}"
                   class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $status === 'active' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Hutang Aktif
                </a>
                <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'unpaid'])) }}"
                   class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $status === 'unpaid' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Belum Dicicil
                </a>
                <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'partial'])) }}"
                   class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $status === 'partial' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Dicicil Sebagian
                </a>
                <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'paid'])) }}"
                   class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $status === 'paid' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Lunas
                </a>
            </div>

            <!-- Search & Actions -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
                <form method="GET" action="{{ route('admin.loans.index') }}" class="flex items-center gap-2">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <div class="relative w-full sm:w-60">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari pinjaman / pihak lain..."
                               class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    </div>
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                        Cari
                    </button>
                    @if(request()->anyFilled(['q', 'status']))
                        <a href="{{ route('admin.loans.index') }}" class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors">
                            Reset
                        </a>
                    @endif
                </form>

                <button type="button" @click="createModal = true"
                        class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95 whitespace-nowrap">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span>Catat Pinjaman Baru</span>
                </button>
            </div>

        </div>
    </section>

    <!-- 3. DATA TABLE PINJAMAN -->
    <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">NO. REF &amp; TANGGAL</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">PEMBERI PINJAMAN</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">KEPERLUAN / TERKAIT PIUTANG</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-right">TOTAL PINJAMAN</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-right">SUDAH DIBAYAR</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-right">SISA HUTANG</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">JATUH TEMPO</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">STATUS</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($loans as $loan)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Ref & Date -->
                            <td class="py-3.5 px-5 font-mono text-xs">
                                <a href="{{ route('admin.loans.show', $loan) }}" class="font-bold text-brand-700 hover:underline">
                                    {{ $loan->reference_no }}
                                </a>
                                <p class="text-[11px] text-slate-400">{{ $loan->loan_date->translatedFormat('d M Y') }}</p>
                            </td>

                            <!-- Lender -->
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $loan->lender_name }}</span>
                                @if($loan->recorder)
                                    <p class="text-[10px] text-slate-400">Dicatat: {{ $loan->recorder->name }}</p>
                                @endif
                            </td>

                            <!-- Purpose & Linked Order -->
                            <td class="py-3.5 px-5 max-w-xs truncate">
                                <span class="font-medium text-slate-800 block">{{ $loan->title }}</span>
                                @if($loan->order)
                                    <p class="text-[11px] text-amber-600 font-semibold mt-0.5">
                                        <i class="fa-solid fa-link text-[10px]"></i> Menalangi:
                                        <a href="{{ route('admin.orders.show', $loan->order) }}" class="underline hover:text-amber-800">
                                            {{ $loan->order->order_number }}
                                        </a>
                                        (Piutang: {{ rupiah($loan->order->remaining) }})
                                    </p>
                                @endif
                            </td>

                            <!-- Amounts -->
                            <td class="py-3.5 px-5 text-right font-bold text-slate-900">
                                {{ rupiah($loan->amount) }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-semibold text-emerald-600">
                                {{ rupiah($loan->amount_paid) }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-bold {{ $loan->remaining_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ rupiah($loan->remaining_amount) }}
                            </td>

                            <!-- Due Date -->
                            <td class="py-3.5 px-5 text-center">
                                @if($loan->due_date)
                                    <span class="{{ $loan->is_due_soon ? 'font-bold text-rose-600' : 'text-slate-600' }}">
                                        {{ $loan->due_date->translatedFormat('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-5 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $loan->status_color }}">
                                    {{ $loan->status_label }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($loan->status !== \App\Models\Loan::STATUS_PAID)
                                        <button type="button"
                                                @click="openPayModal({{ json_encode([
                                                    'id' => $loan->id,
                                                    'reference_no' => $loan->reference_no,
                                                    'lender_name' => $loan->lender_name,
                                                    'amount' => $loan->amount,
                                                    'amount_paid' => $loan->amount_paid,
                                                    'remaining_amount' => $loan->remaining_amount,
                                                    'remaining_formatted' => rupiah($loan->remaining_amount),
                                                ]) }})"
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                                title="Bayar / Cicil Pinjaman">
                                            Cicil
                                        </button>
                                    @endif

                                    <a href="{{ route('admin.loans.show', $loan) }}"
                                       class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                       title="Lihat Detail & Riwayat Cicilan">
                                        <i class="fa-regular fa-eye text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                Belum ada catatan pinjaman.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loans->hasPages() || $loans->total() > 0)
        <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
            <span>Menampilkan {{ $loans->count() }} dari {{ $loans->total() }} catatan pinjaman</span>
            <div>
                {{ $loans->links() }}
            </div>
        </div>
        @endif
    </section>

    {{-- MODAL 1: CATAT PINJAMAN BARU --}}
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="createModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="createModal = false"
                 class="fixed inset-0 bg-neutral-900/60 transition-opacity backdrop-blur-xs"></div>

            <div x-show="createModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-lg transform rounded-2xl bg-white p-6 text-left shadow-2xl transition-all">

                <div class="flex items-center justify-between border-b border-line pb-3.5">
                    <div class="flex items-center gap-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-ink">Catat Pinjaman / Dana Talangan</h3>
                            <p class="text-xs text-neutral-500">Mencatat penerimaan dana pinjaman dari pihak lain</p>
                        </div>
                    </div>
                    <button type="button" @click="createModal = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.loans.store') }}" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Nama Pemberi Pinjaman / Kreditur <span class="text-red-500">*</span></label>
                        <input type="text" name="lender_name" required placeholder="Contoh: Pak Haji Rahmat, Rekan Konveksi, Bank"
                               class="form-input mt-1 w-full text-sm">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Nominal Pinjaman (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" required min="1000" step="1000" placeholder="Contoh: 10000000"
                                   class="form-input mt-1 w-full text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Tanggal Diterima <span class="text-red-500">*</span></label>
                            <input type="date" name="loan_date" required value="{{ now()->toDateString() }}"
                                   class="form-input mt-1 w-full text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Target Jatuh Tempo (Opsional)</label>
                        <input type="date" name="due_date"
                               class="form-input mt-1 w-full text-sm">
                        <p class="mt-1 text-[11px] text-neutral-400">Kosongkan jika pinjaman tidak memiliki tenggat waktu tetap.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Tautkan ke Pesanan Tertentu (Opsional)</label>
                        <select name="order_id" class="form-input mt-1 w-full text-xs">
                            <option value="">— Tidak Terikat Pesanan (Pinjaman Umum) —</option>
                            @foreach($ordersWithReceivables as $ord)
                                <option value="{{ $ord->id }}">
                                    {{ $ord->order_number }} · {{ $ord->customer->name }} (Piutang: {{ rupiah($ord->remaining) }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-neutral-500">
                            💡 <em>Pilih pesanan jika pinjaman ini untuk menalangi produksi pesanan yang piutangnya belum dibayar customer.</em>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Keperluan / Keterangan Singkat</label>
                        <input type="text" name="purpose" placeholder="Contoh: Talangan beli kain seragam drill 5 roll"
                               class="form-input mt-1 w-full text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" rows="2" placeholder="Nomor rekening pengembalian, kesepakatan, dll..."
                                  class="form-input mt-1 w-full text-sm"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-line">
                        <button type="button" @click="createModal = false" class="btn-outline !py-2 !px-4 text-xs font-semibold">Batal</button>
                        <button type="submit" class="btn-primary !py-2 !px-5 text-xs font-bold">Simpan Pinjaman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 2: BAYAR / CICIL PINJAMAN --}}
    <div x-show="payModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
                    <div class="flex items-center gap-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-ink">Bayar / Cicil Pinjaman</h3>
                            <p class="text-xs text-neutral-500" x-text="activeLoan ? activeLoan.reference_no + ' (' + activeLoan.lender_name + ')' : ''"></p>
                        </div>
                    </div>
                    <button type="button" @click="payModal = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <template x-if="activeLoan">
                    <form method="POST" :action="'/admin/loans/' + activeLoan.id + '/payments'" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf

                        {{-- Info Sisa Hutang --}}
                        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3 flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Sisa Hutang Saat Ini</p>
                                <p class="text-lg font-extrabold text-rose-700" x-text="activeLoan.remaining_formatted"></p>
                            </div>
                            <button type="button"
                                    @click="payAmount = activeLoan.remaining_amount"
                                    class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700 shadow-xs transition">
                                Lunasi Penuh
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Nominal Pembayaran (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" required min="1000" :max="activeLoan.remaining_amount"
                                   x-model="payAmount"
                                   class="form-input mt-1 w-full text-base font-extrabold text-ink">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-neutral-700">Tanggal Bayar <span class="text-red-500">*</span></label>
                                <input type="date" name="payment_date" required value="{{ now()->toDateString() }}"
                                       class="form-input mt-1 w-full text-xs">
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
                            <input type="text" name="notes" placeholder="Contoh: Cicilan ke-1 dari hasil pelunasan order..."
                                   class="form-input mt-1 w-full text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-neutral-700">Upload Bukti Transfer / Nota (Opsional)</label>
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
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
