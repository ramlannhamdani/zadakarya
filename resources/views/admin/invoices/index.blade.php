@extends('layouts.admin')

@section('title', 'Invoice & Penagihan')

@section('header-actions')
<a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Buat Invoice</span>
</a>
@endsection

@section('content')
<!-- 1. SUMMARY METRICS TILES (Sleek Flat Container) -->
<section class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 lg:gap-6">
        <!-- Total Invoice Ditagihkan -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Nilai Invoice</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-slate-900">{{ rupiah($stats['total_amount']) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $stats['total_count'] }} diterbitkan</p>
        </div>

        <!-- Pembayaran Diterima -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Terbayar</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-emerald-600">{{ rupiah($stats['paid_amount']) }}</span>
            </div>
            <p class="text-[11px] text-emerald-600/80 font-medium mt-0.5">Sudah masuk kas</p>
        </div>

        <!-- Sisa Tagihan (Piutang) -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Sisa Piutang</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-amber-600">{{ rupiah($stats['receivable_amount']) }}</span>
            </div>
            <p class="text-[11px] text-amber-600/80 font-medium mt-0.5">Belum lunas / DP</p>
        </div>

        <!-- Invoice Belum Dibayar -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Belum Ada Bayar</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold {{ $stats['unpaid_count'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $stats['unpaid_count'] }}</span>
                <span class="text-[11px] {{ $stats['unpaid_count'] > 0 ? 'text-rose-600/80' : 'text-slate-400' }} font-medium">invoice</span>
            </div>
            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Perlu tagih WA</p>
        </div>
    </div>
</section>

<!-- 2. FILTER & ACTION TOOLBAR -->
<section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs no-scrollbar">
            <a href="{{ route('admin.invoices.index') }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !$tab ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Semua Invoice ({{ $stats['total_count'] }})
            </a>
            <a href="{{ route('admin.invoices.index', ['tab' => 'unpaid']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $tab === 'unpaid' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Belum Dibayar ({{ $stats['unpaid_count'] }})
            </a>
            <a href="{{ route('admin.invoices.index', ['tab' => 'dp']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $tab === 'dp' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                DP / Sebagian ({{ $stats['dp_count'] }})
            </a>
            <a href="{{ route('admin.invoices.index', ['tab' => 'paid']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $tab === 'paid' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Lunas ({{ $stats['paid_count'] }})
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
            @if($tab)<input type="hidden" name="tab" value="{{ $tab }}">@endif

            <!-- Search Input -->
            <div class="relative flex-1 sm:w-64 sm:flex-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari No. Invoice / Customer..."
                       class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
            </div>

            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors shrink-0">
                Cari
            </button>
            @if(request()->hasAny(['q', 'tab']))
            <a href="{{ route('admin.invoices.index') }}" class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors shrink-0">
                Reset
            </a>
            @endif
        </form>

    </div>
</section>

<!-- 3. DATA TABLE INVOICE -->
<section class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
            <thead>
                <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">NO. INVOICE</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">NO. ORDER</th>
                    <th class="py-3.5 px-5 font-normal uppercase tracking-wider">CUSTOMER</th>
                    <th class="py-3.5 px-5 font-normal uppercase tracking-wider">TANGGAL</th>
                    <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-right">GRAND TOTAL</th>
                    <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">PEMBAYARAN</th>
                    <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3.5 px-5 font-medium text-brand-700 font-mono">
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="hover:underline">{{ $invoice->invoice_number }}</a>
                    </td>
                    <td class="py-3.5 px-5 font-mono text-slate-600">
                        <a href="{{ route('admin.orders.show', $invoice->order) }}" class="hover:text-brand-600 transition-colors">{{ $invoice->order->order_number }}</a>
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="font-bold text-slate-900 block">{{ $invoice->order->customer->name }}</span>
                        @if($invoice->order->customer->company)
                            <span class="text-[11px] text-slate-400">{{ $invoice->order->customer->company }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-slate-500">
                        {{ $invoice->date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-5 font-semibold text-slate-900 text-right">
                        {{ rupiah($invoice->grand_total) }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        @if($invoice->order->payment_status === 'paid')
                            <span class="inline-block px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-semibold text-[11px]">Lunas</span>
                        @elseif($invoice->order->payment_status === 'dp')
                            <span class="inline-block px-2.5 py-0.5 rounded-md bg-amber-50 text-amber-700 font-semibold text-[11px]">DP</span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium text-[11px]">Belum Dibayar</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" title="Lihat Detail Invoice"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-regular fa-eye text-xs"></i>
                            </a>
                            <a href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" title="Unduh PDF"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-regular fa-file-pdf text-xs"></i>
                            </a>
                            <x-admin.invoice-share :invoice="$invoice" label="" small />
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                        Tidak ada data invoice.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer Pagination -->
    @if($invoices->hasPages() || $invoices->total() > 0)
    <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
        <span>Menampilkan {{ $invoices->count() }} dari {{ $invoices->total() }} invoice</span>
        <div>
            {{ $invoices->links() }}
        </div>
    </div>
    @endif
</section>
@endsection
