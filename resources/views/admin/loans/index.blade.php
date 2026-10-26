@extends('layouts.admin')

@section('title', 'Pinjaman')

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

    <div class="space-y-6">
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
                 class="fixed inset-0 bg-slate-900/40 transition-opacity backdrop-blur-xs"></div>

            <div x-show="createModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-lg transform rounded-2xl bg-white p-6 sm:p-7 text-left shadow-2xl border border-slate-100 transition-all">

                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm font-bold shrink-0">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 tracking-tight">Catat Pinjaman Baru</h3>
                            <p class="text-xs text-slate-400">Pencatatan dana pinjaman dari pihak luar / kreditur yang menambah kas</p>
                        </div>
                    </div>
                    <button type="button" @click="createModal = false"
                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.loans.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemberi Pinjaman / Kreditur <span class="text-rose-500">*</span></label>
                        <input type="text" name="lender_name" required placeholder="Contoh: Pak Haji Rahmat, Rekan Konveksi, Bank"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nominal Pinjaman (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="amount" required min="1000" step="1000" placeholder="Contoh: 10000000"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Diterima <span class="text-rose-500">*</span></label>
                            <input type="date" name="loan_date" required value="{{ now()->toDateString() }}"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Jatuh Tempo (Opsional)</label>
                        <input type="date" name="due_date"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                        <p class="mt-1 text-[11px] text-slate-400">Kosongkan jika pinjaman tidak memiliki tenggat waktu tetap.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tautkan ke Pesanan Tertentu (Opsional)</label>
                        <select name="order_id" class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                            <option value="">— Tidak Terikat Pesanan (Pinjaman Umum) —</option>
                            @foreach($ordersWithReceivables as $ord)
                                <option value="{{ $ord->id }}">
                                    {{ $ord->order_number }} · {{ $ord->customer->name }} (Piutang: {{ rupiah($ord->remaining) }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">
                            💡 <em>Pilih pesanan jika pinjaman ini untuk menalangi biaya produksi pesanan yang piutangnya belum dibayar customer.</em>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keperluan / Keterangan Singkat</label>
                        <input type="text" name="purpose" placeholder="Contoh: Talangan beli kain seragam drill 5 roll"
                               class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" rows="2" placeholder="Nomor rekening pengembalian, kesepakatan bagi hasil, dll..."
                                  class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="createModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">Batal</button>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                            <i class="fa-solid fa-check text-[11px]"></i>
                            <span>Simpan Pinjaman</span>
                        </button>
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
                            <h3 class="text-base font-bold text-slate-900 tracking-tight">Bayar / Cicil Pinjaman</h3>
                            <p class="text-xs text-slate-400" x-text="activeLoan ? activeLoan.reference_no + ' (' + activeLoan.lender_name + ')' : ''"></p>
                        </div>
                    </div>
                    <button type="button" @click="payModal = false"
                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <template x-if="activeLoan">
                    <form method="POST" :action="'/admin/loans/' + activeLoan.id + '/payments'" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        {{-- Info Sisa Hutang --}}
                        <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-3.5 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Sisa Hutang Saat Ini</p>
                                <p class="text-lg font-bold font-mono text-rose-700 mt-0.5" x-text="activeLoan.remaining_formatted"></p>
                            </div>
                            <button type="button"
                                    @click="payAmount = activeLoan.remaining_amount"
                                    class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 shadow-xs active:scale-95 transition-all">
                                Lunasi Penuh
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nominal Pembayaran (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="amount" required min="1000" :max="activeLoan.remaining_amount"
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
                            <input type="text" name="notes" placeholder="Contoh: Cicilan ke-1 dari pelunasan pesanan..."
                                   class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Upload Bukti Transfer / Nota (Opsional)</label>
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
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
