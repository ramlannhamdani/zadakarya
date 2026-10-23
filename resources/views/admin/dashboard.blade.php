@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
{{-- Summary cards --}}
<div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
    @foreach([
        ['label' => 'Total Pesanan', 'value' => $stats['total_orders'], 'accent' => 'text-ink'],
        ['label' => 'Pesanan Aktif', 'value' => $stats['active_orders'], 'accent' => 'text-brand-600'],
        ['label' => 'Pesanan Selesai', 'value' => $stats['completed_orders'], 'accent' => 'text-green-600'],
        ['label' => 'Belum Lunas', 'value' => $stats['unpaid_orders'], 'accent' => 'text-amber-600'],
        ['label' => 'Inquiry Baru', 'value' => $stats['new_inquiries'], 'accent' => 'text-warm-600'],
    ] as $card)
        <div class="admin-card">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">{{ $card['label'] }}</p>
            <p class="mt-2 text-3xl font-extrabold {{ $card['accent'] }}">{{ $card['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- Revenue --}}
<div class="mt-6 grid gap-5 lg:grid-cols-3">
    <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
        <div class="admin-card border-l-4 !border-l-green-500">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Pendapatan (Terbayar)</p>
            <p class="mt-2 text-2xl font-extrabold text-green-600">{{ rupiah($revenue['total']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">Seluruh pembayaran yang sudah masuk</p>
        </div>
        <div class="admin-card border-l-4 !border-l-brand-600">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Pendapatan Bulan Ini</p>
            <p class="mt-2 text-2xl font-extrabold text-brand-600">{{ rupiah($revenue['this_month']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">{{ now()->translatedFormat('F Y') }}</p>
        </div>
        <div class="admin-card border-l-4 !border-l-amber-500">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Piutang (Sisa Tagihan)</p>
            <p class="mt-2 text-2xl font-extrabold text-amber-600">{{ rupiah($revenue['receivable']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">Belum dibayar dari pesanan non-batal</p>
        </div>
        <div class="admin-card border-l-4 !border-l-neutral-400">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Nilai Pesanan</p>
            <p class="mt-2 text-2xl font-extrabold text-ink">{{ rupiah($revenue['order_value']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">Grand total semua pesanan non-batal</p>
        </div>
    </div>

    <div class="admin-card">
        <h2 class="font-extrabold text-ink">Pendapatan 6 Bulan Terakhir</h2>
        <div class="mt-5 flex h-40 items-end gap-2">
            @foreach($monthly as $m)
                <div class="group flex flex-1 flex-col items-center justify-end gap-1.5" title="{{ $m['full'] }}: {{ rupiah($m['amount']) }}">
                    <span class="text-[10px] font-semibold text-neutral-500 opacity-0 transition group-hover:opacity-100">{{ $m['amount'] > 0 ? number_format($m['amount'] / 1000000, 1, ',', '.').' jt' : '' }}</span>
                    <div class="w-full rounded-t-md {{ $m['amount'] > 0 ? 'bg-brand-600' : 'bg-line' }}" style="height: {{ max(4, round($m['amount'] / $monthlyMax * 100)) }}%"></div>
                    <span class="text-xs font-medium text-neutral-500">{{ $m['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Profit & Overhead Breakdown (Laba Kotor vs Beban Operasional vs Laba Bersih) --}}
<div class="mt-6">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h2 class="text-base font-extrabold text-ink">Laba Perusahaan &amp; Beban Operasional</h2>
            <p class="text-xs text-neutral-500">Perhitungan keuntungan global setelah dikurangi biaya produksi (HPP) dan beban operasional (OPEX).</p>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('admin.expenses.index'))
        <a href="{{ route('admin.expenses.index') }}" class="text-xs font-bold text-brand-600 hover:underline inline-flex items-center gap-1">
            <span>Kelola Beban Operasional</span>
            <span>&rarr;</span>
        </a>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="admin-card border-l-4 !border-l-blue-500">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total HPP Produksi</p>
            <p class="mt-2 text-2xl font-extrabold text-blue-600">{{ rupiah($financials['total_hpp']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">Biaya kain, trims, makloon & CMT pesanan</p>
        </div>

        <div class="admin-card border-l-4 !border-l-indigo-500">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Laba Kotor Pesanan</p>
            <p class="mt-2 text-2xl font-extrabold text-indigo-600">{{ rupiah($financials['gross_profit']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">Total Nilai Order − Total HPP</p>
        </div>

        <div class="admin-card border-l-4 !border-l-rose-500">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Beban Operasional</p>
                <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">Listrik, Sewa, Gaji dll</span>
            </div>
            <p class="mt-2 text-2xl font-extrabold text-rose-600">{{ rupiah($financials['total_expenses']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">Bulan ini: <strong class="text-ink">{{ rupiah($financials['this_month_expenses']) }}</strong></p>
        </div>

        <div class="admin-card border-l-4 {{ $financials['net_profit'] >= 0 ? '!border-l-emerald-500 bg-emerald-50/20' : '!border-l-red-500 bg-red-50/20' }}">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Laba Bersih Perusahaan</p>
            <p class="mt-2 text-2xl font-extrabold {{ $financials['net_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                {{ rupiah($financials['net_profit']) }}
            </p>
            <p class="mt-1 text-xs text-neutral-500">Laba Kotor − Beban Operasional</p>
        </div>

        <div class="admin-card border-l-4 {{ ($financials['total_cash_advances'] ?? 0) > 0 ? '!border-l-amber-500 bg-amber-50/30' : '!border-l-teal-500 bg-teal-50/20' }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Talangan Kas (Nombok)</p>
                @if(($financials['deficit_orders_count'] ?? 0) > 0)
                    <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="text-[10px] font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 px-2 py-0.5 rounded transition">
                        {{ $financials['deficit_orders_count'] }} Order &rarr;
                    </a>
                @endif
            </div>
            <p class="mt-2 text-2xl font-extrabold {{ ($financials['total_cash_advances'] ?? 0) > 0 ? 'text-amber-700' : 'text-teal-700' }}">
                {{ rupiah($financials['total_cash_advances'] ?? 0) }}
            </p>
            <p class="mt-1 text-xs text-neutral-500">
                @if(($financials['total_cash_advances'] ?? 0) > 0)
                    Kas kantor menombok belanja HPP > DP
                @else
                    Semua belanja HPP tertutup DP
                @endif
            </p>
        </div>
    </div>
</div>

{{-- Action required --}}
<div class="mt-6 grid gap-5 xl:grid-cols-3">
    <div class="admin-card">
        <h2 class="font-extrabold text-ink">Perlu Tindakan</h2>
        <ul class="mt-4 space-y-3 text-sm">
            @if($actionRequired['new_inquiries'] > 0)
                <li>
                    <a href="{{ route('admin.inquiries.index', ['status' => 'new']) }}" class="flex items-center justify-between rounded-lg bg-amber-50 px-4 py-3 font-medium text-amber-800 transition hover:bg-amber-100">
                        {{ $actionRequired['new_inquiries'] }} inquiry belum ditindaklanjuti
                        <span>&rarr;</span>
                    </a>
                </li>
            @endif
            @if($actionRequired['awaiting_dp'] > 0)
                <li>
                    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="flex items-center justify-between rounded-lg bg-red-50 px-4 py-3 font-medium text-red-800 transition hover:bg-red-100">
                        {{ $actionRequired['awaiting_dp'] }} pesanan belum menerima DP
                        <span>&rarr;</span>
                    </a>
                </li>
            @endif
            @if($actionRequired['in_production'] > 0)
                <li>
                    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3 font-medium text-blue-800 transition hover:bg-blue-100">
                        {{ $actionRequired['in_production'] }} pesanan sedang produksi
                        <span>&rarr;</span>
                    </a>
                </li>
            @endif
            @if($actionRequired['near_deadline'] > 0)
                <li>
                    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="flex items-center justify-between rounded-lg bg-brand-50 px-4 py-3 font-medium text-brand-600 transition hover:bg-brand-100">
                        {{ $actionRequired['near_deadline'] }} pesanan mendekati deadline (&le;7 hari)
                        <span>&rarr;</span>
                    </a>
                </li>
            @endif
            @if(($actionRequired['cash_deficits'] ?? 0) > 0)
                <li>
                    <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="flex items-center justify-between rounded-lg bg-amber-50/90 px-4 py-3 font-medium text-amber-900 transition hover:bg-amber-100 border border-amber-200">
                        <div class="pr-2">
                            <span class="font-bold text-amber-900">{{ $actionRequired['cash_deficits'] }} pesanan nombok kas ({{ rupiah($actionRequired['cash_deficits_amount'] ?? 0) }})</span>
                            <span class="block text-xs font-normal text-amber-700">Belanja HPP > DP masuk &bull; kas kantor menalangi sampai pelunasan</span>
                        </div>
                        <span class="text-amber-800 font-bold">&rarr;</span>
                    </a>
                </li>
            @endif
            @if(array_sum(array_filter($actionRequired, fn ($k) => $k !== 'cash_deficits_amount', ARRAY_FILTER_USE_KEY)) === 0)
                <li class="rounded-lg bg-green-50 px-4 py-3 font-medium text-green-700">Semua beres — tidak ada tindakan mendesak. 🎉</li>
            @endif
        </ul>

        <h2 class="mt-6 font-extrabold text-ink">Inquiry Terbaru</h2>
        <ul class="mt-3 divide-y divide-line text-sm">
            @forelse($recentInquiries as $inquiry)
                <li>
                    <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="flex items-center justify-between gap-2 py-2.5 hover:text-brand-600">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">{{ $inquiry->name }}</span>
                            <span class="block truncate text-xs text-neutral-500">{{ $inquiry->service_name ?: 'Umum' }} &bull; {{ $inquiry->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $inquiry->status === 'new' ? 'bg-amber-100 text-amber-700' : 'bg-neutral-100 text-neutral-600' }}">{{ $inquiry->status_label }}</span>
                    </a>
                </li>
            @empty
                <li class="py-2.5 text-neutral-500">Belum ada inquiry.</li>
            @endforelse
        </ul>
    </div>

    {{-- Recent orders --}}
    <div class="admin-card xl:col-span-2">
        <div class="flex items-center justify-between">
            <h2 class="font-extrabold text-ink">Pesanan Terbaru</h2>
            <a href="{{ route('admin.orders.create') }}" class="btn-primary !px-4 !py-2 text-xs">+ Buat Pesanan</a>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                        <th class="pb-2.5 pr-4">No. Order</th>
                        <th class="pb-2.5 pr-4">Customer</th>
                        <th class="pb-2.5 pr-4">Total</th>
                        <th class="pb-2.5 pr-4">Pembayaran</th>
                        <th class="pb-2.5 pr-4">Tahap</th>
                        <th class="pb-2.5">Deadline</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-cream/60">
                            <td class="py-3 pr-4">
                                <a href="{{ route('admin.orders.show', $order) }}" class="whitespace-nowrap font-mono font-bold text-brand-600 hover:underline">{{ $order->order_number }}</a>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="block font-medium text-ink">{{ $order->customer->name }}</span>
                                <span class="block text-xs text-neutral-500">{{ $order->items->first()?->product_name }}{{ $order->items->count() > 1 ? ' +'.($order->items->count()-1) : '' }}</span>
                            </td>
                            <td class="whitespace-nowrap py-3 pr-4 font-semibold">{{ rupiah($order->grand_total) }}</td>
                            <td class="py-3 pr-4"><x-payment-badge :status="$order->payment_status" /></td>
                            <td class="py-3 pr-4 text-neutral-600">{{ $order->current_stage_name }}</td>
                            <td class="py-3 text-neutral-600">{{ $order->deadline?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-neutral-500">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
