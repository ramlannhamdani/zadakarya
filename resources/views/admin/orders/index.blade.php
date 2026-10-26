@extends('layouts.admin')

@section('title', 'Daftar Pesanan')

@section('header-actions')
<a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Buat Pesanan Baru</span>
</a>
@endsection

@section('content')
<!-- 1. SUMMARY METRICS TILES (Sleek Flat Container) -->
<section class="bg-white rounded-2xl p-6 shadow-sm">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
        <!-- Total Pesanan -->
        <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Semua Order</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-slate-900">{{ $stats['total'] }}</span>
                <span class="text-[11px] text-slate-400">transaksi</span>
            </div>
        </div>

        <!-- Pesanan Dalam Produksi -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Sedang Berjalan</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-amber-600">{{ $stats['active'] }}</span>
                <span class="text-[11px] text-amber-600/80 font-medium">proses konveksi</span>
            </div>
        </div>

        <!-- Pesanan Selesai -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Pesanan Selesai</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-emerald-600">{{ $stats['completed'] }}</span>
                <span class="text-[11px] text-emerald-600/80 font-medium">terkirim / lunas</span>
            </div>
        </div>

        <!-- Menunggu Pelunasan -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Belum Lunas</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-slate-700">{{ $stats['unpaid'] }}</span>
                <span class="text-[11px] text-slate-400 font-medium">order</span>
            </div>
            @if(($readyToBillCount ?? 0) > 0)
            <div class="mt-2 text-[11px] text-rose-600 font-semibold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>{{ $readyToBillCount }} Siap Ditagih ({{ rupiah($readyToBillReceivable ?? 0) }})</span>
            </div>
            @endif
        </div>
    </div>
</section>

<!-- 2. FILTER & ACTION TOOLBAR -->
<section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs">
            <a href="{{ route('admin.orders.index') }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ (!$cashStatus && !$status && !$filter) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Semua ({{ $stats['total'] }})
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'active']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ (!$cashStatus && !$filter && $status === 'active') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Aktif ({{ $stats['active'] }})
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'completed']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ (!$cashStatus && !$filter && $status === 'completed') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Selesai ({{ $stats['completed'] }})
            </a>
            @if(($readyToBillCount ?? 0) > 0)
            <a href="{{ route('admin.orders.index', ['filter' => 'ready_to_bill']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap inline-flex items-center gap-1.5 {{ $filter === 'ready_to_bill' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                <span>🚨 Siap Ditagih</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $filter === 'ready_to_bill' ? 'bg-white text-rose-700' : 'bg-rose-200 text-rose-900' }} font-bold">{{ $readyToBillCount }}</span>
            </a>
            @endif
            @if(($deficitCount ?? 0) > 0)
            <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap inline-flex items-center gap-1.5 {{ $cashStatus === 'deficit' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                <span>⚠️ Nombok Kas</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $cashStatus === 'deficit' ? 'bg-white text-amber-700' : 'bg-amber-200 text-amber-900' }} font-bold">{{ $deficitCount }}</span>
            </a>
            @endif
            <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ (!$cashStatus && !$filter && $status === 'cancelled') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Dibatalkan
            </a>
        </div>

        <!-- Search & Filter Options -->
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            @if($cashStatus)<input type="hidden" name="cash_status" value="{{ $cashStatus }}">@endif
            @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif

            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari No. Order / Customer..."
                       class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
            </div>

            <!-- Submit Button -->
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Cari
            </button>
            @if(request()->hasAny(['q', 'status', 'cash_status', 'filter']))
            <a href="{{ route('admin.orders.index') }}" class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors">
                Reset
            </a>
            @endif
        </form>

    </div>
</section>

<!-- 3. DATA TABLE PESANAN -->
<section class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
            <thead>
                <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                    <th class="py-3.5 px-5 font-normal">NO. ORDER</th>
                    <th class="py-3.5 px-5 font-normal">CUSTOMER</th>
                    <th class="py-3.5 px-5 font-normal">PRODUK</th>
                    <th class="py-3.5 px-5 font-normal text-right">TOTAL</th>
                    <th class="py-3.5 px-5 font-normal text-center">PEMBAYARAN</th>
                    <th class="py-3.5 px-5 font-normal text-center">TALANGAN KAS</th>
                    <th class="py-3.5 px-5 font-normal">TAHAP</th>
                    <th class="py-3.5 px-5 font-normal text-center">DEADLINE</th>
                    <th class="py-3.5 px-5 font-normal text-center">STATUS</th>
                    <th class="py-3.5 px-5 font-normal text-center">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($orders as $order)
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3.5 px-5 font-medium text-brand-700 font-mono">
                        <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">{{ $order->order_number }}</a>
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="font-bold text-slate-900 block">{{ $order->customer->name }}</span>
                        @if($order->customer->company)
                            <span class="text-[11px] text-slate-400">{{ $order->customer->company }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="text-slate-600 block">{{ $order->items->first()?->product_name ?? '—' }}</span>
                        @if($order->items->count() > 1)
                            <span class="text-[11px] text-slate-400">+{{ $order->items->count() - 1 }} item lainnya</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 font-semibold text-slate-900 text-right">
                        {{ rupiah($order->grand_total) }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <div class="inline-flex flex-col items-center gap-1">
                            @if($order->status === 'completed' && $order->payment_status !== 'paid')
                                <span class="inline-block px-2.5 py-0.5 rounded-md bg-rose-50 text-rose-700 font-bold text-[11px]">
                                    🚨 Siap Ditagih
                                </span>
                                <span class="text-[10px] text-rose-600 font-semibold">Sisa {{ rupiah($order->remaining) }}</span>
                            @elseif($order->payment_status === 'paid')
                                <span class="inline-block px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-semibold text-[11px]">Lunas</span>
                            @elseif($order->payment_status === 'dp')
                                <span class="inline-block px-2.5 py-0.5 rounded-md bg-amber-50 text-amber-700 font-semibold text-[11px]">DP Terbayar</span>
                                <span class="text-[10px] text-slate-400">Sisa {{ rupiah($order->remaining) }}</span>
                            @else
                                <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium text-[11px]">Belum Dibayar</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        @if($order->is_cash_deficit)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 text-[10px] font-bold" title="HPP Belanja {{ rupiah($order->total_cost) }} > Uang Masuk {{ rupiah($order->amount_paid) }}">
                                <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                                <span>Nombok {{ rupiah($order->cash_deficit) }}</span>
                            </span>
                        @elseif($order->total_cost > 0)
                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-700 font-medium">
                                <i class="fa-solid fa-check text-[9px]"></i>
                                <span>Aman (Tertutup DP)</span>
                            </span>
                        @else
                            <span class="text-[11px] text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-slate-600">
                        <span class="block">{{ $order->current_stage_name }}</span>
                        <span class="text-[10px] text-slate-400">Tahap {{ $order->current_stage }}/7</span>
                    </td>
                    <td class="py-3.5 px-5 text-center {{ $order->deadline && $order->deadline->isPast() ? 'text-rose-500 font-semibold' : 'text-slate-500' }}">
                        {{ $order->deadline?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        @if($order->status === 'active')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                Aktif
                            </span>
                        @elseif($order->status === 'completed')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-medium text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Selesai
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                {{ ucfirst($order->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.orders.show', $order) }}" title="Lihat Detail"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-regular fa-eye text-xs"></i>
                            </a>
                            <a href="{{ route('admin.orders.edit', $order) }}" title="Edit Pesanan"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                        Tidak ada data pesanan yang cocok.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer Pagination -->
    @if($orders->hasPages() || $orders->total() > 0)
    <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
        <span>Menampilkan {{ $orders->count() }} dari {{ $orders->total() }} data pesanan</span>
        <div>
            {{ $orders->links() }}
        </div>
    </div>
    @endif
</section>
@endsection
