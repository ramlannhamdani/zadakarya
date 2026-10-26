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

    {{-- Top Summary KPI Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Total Sisa Hutang Aktif --}}
        <div class="admin-card border-l-4 !border-l-rose-500 bg-rose-50/10">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Sisa Hutang Aktif</p>
                <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Kewajiban</span>
            </div>
            <p class="mt-2 text-2xl font-extrabold text-rose-600">{{ rupiah($totalActiveDebt) }}</p>
            <p class="mt-1 text-xs text-neutral-500">
                Dari total pinjaman tercatat <strong class="font-semibold text-ink">{{ rupiah($totalDebtRecorded) }}</strong>
            </p>
        </div>

        {{-- Card 2: Pinjaman Diterima Bulan Ini --}}
        <div class="admin-card border-l-4 !border-l-indigo-600 bg-indigo-50/10">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Pinjaman Masuk (Bulan Ini)</p>
                <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700">Dana Talangan</span>
            </div>
            <p class="mt-2 text-2xl font-extrabold text-indigo-700">{{ rupiah($loansThisMonth) }}</p>
            <p class="mt-1 text-xs text-neutral-500">Total uang pinjaman diterima bulan ini</p>
        </div>

        {{-- Card 3: Pelunasan / Cicilan Bulan Ini --}}
        <div class="admin-card border-l-4 !border-l-emerald-600 bg-emerald-50/10">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Pelunasan (Bulan Ini)</p>
                <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">Kas Keluar</span>
            </div>
            <p class="mt-2 text-2xl font-extrabold text-emerald-700">{{ rupiah($repaymentsThisMonth) }}</p>
            <p class="mt-1 text-xs text-neutral-500">
                Total lunas sepanjang waktu: <strong class="font-semibold text-ink">{{ rupiah($totalRepaidAllTime) }}</strong>
            </p>
        </div>

        {{-- Card 4: Jatuh Tempo Mendekat --}}
        <div class="admin-card border-l-4 {{ $dueSoonCount > 0 ? '!border-l-amber-500 bg-amber-50/20' : '!border-l-neutral-300' }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Jatuh Tempo Mendekat</p>
                <span class="rounded {{ $dueSoonCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-neutral-100 text-neutral-600' }} px-1.5 py-0.5 text-[10px] font-bold">
                    ≤ 7 Hari
                </span>
            </div>
            <p class="mt-2 text-2xl font-extrabold {{ $dueSoonCount > 0 ? 'text-amber-700' : 'text-neutral-700' }}">
                {{ $dueSoonCount }} <span class="text-sm font-medium">Pinjaman</span>
            </p>
            <p class="mt-1 text-xs text-neutral-500">Target pelunasan dalam waktu dekat</p>
        </div>
    </div>

    {{-- Filter, Search, & Actions --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{-- Status Filter Tabs --}}
        <div class="flex flex-wrap items-center gap-1.5 rounded-xl border border-line bg-white p-1.5 text-xs font-semibold shadow-xs">
            <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'all'])) }}"
               class="rounded-lg px-3 py-1.5 transition {{ $status === 'all' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:bg-cream hover:text-ink' }}">
                Semua
            </a>
            <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'active'])) }}"
               class="rounded-lg px-3 py-1.5 transition {{ $status === 'active' ? 'bg-rose-600 text-white shadow-xs' : 'text-neutral-600 hover:bg-cream hover:text-ink' }}">
                Hutang Aktif
            </a>
            <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'unpaid'])) }}"
               class="rounded-lg px-3 py-1.5 transition {{ $status === 'unpaid' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:bg-cream hover:text-ink' }}">
                Belum Dicicil
            </a>
            <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'partial'])) }}"
               class="rounded-lg px-3 py-1.5 transition {{ $status === 'partial' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:bg-cream hover:text-ink' }}">
                Dicicil Sebagian
            </a>
            <a href="{{ route('admin.loans.index', array_merge(request()->query(), ['status' => 'paid'])) }}"
               class="rounded-lg px-3 py-1.5 transition {{ $status === 'paid' ? 'bg-emerald-600 text-white shadow-xs' : 'text-neutral-600 hover:bg-cream hover:text-ink' }}">
                Lunas
            </a>
        </div>

        {{-- Search & New Button --}}
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('admin.loans.index') }}" class="flex items-center gap-1.5">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative">
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="Cari pinjaman / pihak lain..."
                           class="form-input !py-2 !text-xs !pr-8 sm:w-64">
                    @if(request('q'))
                        <a href="{{ route('admin.loans.index', ['status' => request('status')]) }}"
                           class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600">
                            ✕
                        </a>
                    @endif
                </div>
                <button type="submit" class="btn-outline !py-2 !px-3 !text-xs font-semibold">Cari</button>
            </form>

            <button type="button" @click="createModal = true"
                    class="btn-primary flex items-center gap-1.5 !py-2 !px-3.5 !text-xs font-bold whitespace-nowrap shadow-xs">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span>Catat Pinjaman Baru</span>
            </button>
        </div>
    </div>

    {{-- Loans Table --}}
    <div class="admin-card mt-4 overflow-x-auto !p-0">
        <table class="w-full min-w-[900px] text-sm">
            <thead>
                <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                    <th class="px-5 py-3">No. Ref &amp; Tanggal</th>
                    <th class="px-5 py-3">Pemberi Pinjaman</th>
                    <th class="px-5 py-3">Keperluan / Terkait Piutang</th>
                    <th class="px-5 py-3 text-right">Total Pinjaman</th>
                    <th class="px-5 py-3 text-right">Sudah Dibayar</th>
                    <th class="px-5 py-3 text-right">Sisa Hutang</th>
                    <th class="px-5 py-3 text-center">Jatuh Tempo</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($loans as $loan)
                    <tr class="hover:bg-cream/40 transition">
                        {{-- Ref & Date --}}
                        <td class="px-5 py-3.5">
                            <a href="{{ route('admin.loans.show', $loan) }}" class="font-mono font-bold text-brand-600 hover:underline">
                                {{ $loan->reference_no }}
                            </a>
                            <p class="text-xs text-neutral-500">{{ $loan->loan_date->translatedFormat('d M Y') }}</p>
                        </td>

                        {{-- Lender --}}
                        <td class="px-5 py-3.5">
                            <span class="font-bold text-ink">{{ $loan->lender_name }}</span>
                            @if($loan->recorder)
                                <p class="text-[11px] text-neutral-400">Dicatat: {{ $loan->recorder->name }}</p>
                            @endif
                        </td>

                        {{-- Purpose & Linked Order --}}
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-neutral-700">{{ $loan->purpose ?: 'Dana Talangan Operasional' }}</p>
                            @if($loan->order)
                                <div class="mt-1 flex items-center gap-1.5 text-xs text-indigo-700">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                                    <a href="{{ route('admin.orders.show', $loan->order) }}" class="font-semibold hover:underline">
                                        {{ $loan->order->order_number }}
                                    </a>
                                    <span class="text-neutral-400">({{ $loan->order->customer->name }})</span>
                                    @if($loan->order->remaining > 0)
                                        <span class="rounded bg-amber-50 px-1 py-0.2 text-[10px] font-bold text-amber-700 border border-amber-200">
                                            Piutang {{ rupiah($loan->order->remaining) }}
                                        </span>
                                    @else
                                        <span class="rounded bg-emerald-50 px-1 py-0.2 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                            Order Lunas
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        {{-- Total Amount --}}
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold text-ink">
                            {{ rupiah($loan->amount) }}
                        </td>

                        {{-- Paid Amount --}}
                        <td class="whitespace-nowrap px-5 py-3.5 text-right text-emerald-700 font-semibold">
                            {{ rupiah($loan->amount_paid) }}
                            <div class="mt-1 w-24 ml-auto h-1.5 rounded-full bg-neutral-200 overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $loan->repaid_percentage }}%"></div>
                            </div>
                        </td>

                        {{-- Remaining Debt --}}
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-extrabold {{ $loan->remaining_amount > 0 ? 'text-rose-600' : 'text-neutral-400' }}">
                            {{ rupiah($loan->remaining_amount) }}
                        </td>

                        {{-- Due Date --}}
                        <td class="px-5 py-3.5 text-center text-xs">
                            @if($loan->due_date)
                                <span class="{{ $loan->is_overdue ? 'font-bold text-rose-600' : 'text-neutral-600' }}">
                                    {{ $loan->due_date->translatedFormat('d M Y') }}
                                </span>
                                @if($loan->is_overdue)
                                    <span class="block text-[10px] font-bold text-rose-600 uppercase">Jatuh Tempo!</span>
                                @endif
                            @else
                                <span class="text-neutral-400">—</span>
                            @endif
                        </td>

                        {{-- Status Badge --}}
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $loan->status_color }}">
                                {{ $loan->status_label }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
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
                                            class="rounded-md border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition shadow-xs"
                                            title="Bayar / Cicil Pinjaman">
                                        Bayar
                                    </button>
                                @endif

                                <a href="{{ route('admin.loans.show', $loan) }}"
                                   class="rounded-md border border-neutral-300 bg-white px-2.5 py-1 text-xs font-semibold text-neutral-700 hover:bg-cream transition"
                                   title="Lihat Detail & Riwayat Cicilan">
                                    Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center text-neutral-500">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-cream text-neutral-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                            </div>
                            <p class="mt-3 font-semibold text-neutral-700">Belum ada catatan pinjaman</p>
                            <p class="text-xs text-neutral-500">Klik tombol "Catat Pinjaman Baru" di atas untuk mencatat dana talangan yang diterima.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $loans->links() }}
    </div>

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
