@extends('layouts.admin')

@section('title', 'Catatan Pembayaran & Arus Kas Masuk')

@section('content')
<!-- 1. SUMMARY METRICS TILES -->
<section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    <!-- Total Dana Diterima Keseluruhan -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Kas Masuk</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-xl sm:text-2xl font-bold text-emerald-600 truncate">{{ rupiah($stats['total']) }}</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-0.5 truncate">Semua riwayat pesanan</p>
    </div>

    <!-- Penerimaan Bulan Ini -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Penerimaan Bulan Ini</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-xl sm:text-2xl font-bold text-brand-700 truncate">{{ rupiah($stats['this_month']) }}</span>
        </div>
        <p class="text-[11px] text-brand-600 font-medium mt-0.5 truncate">{{ now()->translatedFormat('F Y') }} berjalan</p>
    </div>

    <!-- Pembayaran Transfer Bank -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Transfer Bank</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-xl sm:text-2xl font-bold text-slate-900 truncate">{{ rupiah($stats['transfer']) }}</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $stats['transfer_count'] }} transaksi via bank</p>
    </div>

    <!-- Pembayaran Tunai / Cash -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tunai / Cash Workshop</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-xl sm:text-2xl font-bold text-slate-900 truncate">{{ rupiah($stats['cash']) }}</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $stats['cash_count'] }} transaksi cash langsung</p>
    </div>
</section>

<!-- 2. FILTER & ACTION TOOLBAR -->
<section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs no-scrollbar">
            <a href="{{ route('admin.payments.index') }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !$tab ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Semua Pembayaran ({{ $stats['count'] }})
            </a>
            <a href="{{ route('admin.payments.index', ['tab' => 'transfer']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $tab === 'transfer' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Transfer Bank ({{ $stats['transfer_count'] }})
            </a>
            <a href="{{ route('admin.payments.index', ['tab' => 'cash']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $tab === 'cash' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Tunai / Cash ({{ $stats['cash_count'] }})
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex items-center gap-2 w-full lg:w-auto">
            @if($tab)<input type="hidden" name="tab" value="{{ $tab }}">@endif

            <!-- Search Input -->
            <div class="relative flex-1 sm:w-64 sm:flex-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari No. Order / Customer / Ref..."
                       class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
            </div>

            <button type="submit" class="shrink-0 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Cari
            </button>
            @if(request()->hasAny(['q', 'tab']))
            <a href="{{ route('admin.payments.index') }}" class="shrink-0 px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors">
                Reset
            </a>
            @endif
        </form>

    </div>
</section>

<!-- 3. DATA TABLE PEMBAYARAN -->
<section class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
            <thead>
                <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">TANGGAL</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">NO. ORDER</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">CUSTOMER</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-right">NOMINAL</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">METODE</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">INVOICE</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">CATATAN</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($payments as $payment)
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3.5 px-5 text-slate-500">
                        {{ $payment->payment_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-5 font-medium text-brand-700 font-mono">
                        <a href="{{ route('admin.orders.show', ['order' => $payment->order, 'tab' => 'payments']) }}" class="hover:underline">
                            {{ $payment->order->order_number }}
                        </a>
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="font-bold text-slate-900 block">{{ $payment->order->customer->name }}</span>
                        @if($payment->order->customer->company)
                            <span class="text-[11px] text-slate-400">{{ $payment->order->customer->company }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 font-bold text-emerald-600 text-right">
                        {{ rupiah($payment->amount) }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">
                            {{ $payment->method_label }}
                        </span>
                    </td>
                    <td class="py-3.5 px-5 font-mono text-slate-600">
                        @if($payment->invoice)
                            <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="hover:text-brand-600 hover:underline">
                                {{ $payment->invoice->invoice_number }}
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-slate-600">
                        {{ $payment->note ?: ($payment->reference ? 'Ref: '.$payment->reference : '—') }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.orders.show', ['order' => $payment->order, 'tab' => 'payments']) }}" title="Lihat di Pesanan"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-regular fa-eye text-xs"></i>
                            </a>
                            @if($payment->proof)
                            <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank" title="Lihat Bukti Transfer"
                               class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors">
                                <i class="fa-solid fa-paperclip text-xs"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                        Belum ada data pembayaran.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer Pagination -->
    @if($payments->hasPages() || $payments->total() > 0)
    <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
        <span>Menampilkan {{ $payments->count() }} dari {{ $payments->total() }} pembayaran</span>
        <div>
            {{ $payments->links() }}
        </div>
    </div>
    @endif
</section>
@endsection
