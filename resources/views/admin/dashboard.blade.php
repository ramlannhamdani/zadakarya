@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
{{-- Summary cards --}}
<div class="grid gap-3.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
    @foreach([
        ['label' => 'Total Pesanan', 'value' => $stats['total_orders'], 'accent' => 'text-ink'],
        ['label' => 'Pesanan Aktif', 'value' => $stats['active_orders'], 'accent' => 'text-brand-600'],
        ['label' => 'Pesanan Selesai', 'value' => $stats['completed_orders'], 'accent' => 'text-green-600'],
        ['label' => 'Belum Lunas', 'value' => $stats['unpaid_orders'], 'accent' => 'text-amber-600'],
        ['label' => 'Inquiry Baru', 'value' => $stats['new_inquiries'], 'accent' => 'text-warm-600'],
    ] as $card)
        <div class="admin-card !p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-500">{{ $card['label'] }}</p>
            <p class="mt-1.5 text-2xl font-black {{ $card['accent'] }}">{{ $card['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- Revenue --}}
<div class="mt-8 sm:mt-9 grid gap-4 sm:gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-neutral-400 flex flex-col justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-500">Total Nilai Pesanan</p>
            <p class="mt-2.5 text-xl sm:text-2xl font-black text-ink">{{ rupiah($revenue['order_value']) }}</p>
        </div>
        <p class="mt-3 text-xs text-neutral-500">Grand total semua pesanan non-batal</p>
    </div>
    <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-green-500 flex flex-col justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-500">Total Pendapatan (Terbayar)</p>
            <p class="mt-2.5 text-xl sm:text-2xl font-black text-green-600">{{ rupiah($revenue['total']) }}</p>
        </div>
        <p class="mt-3 text-xs text-neutral-500">Seluruh pembayaran yang sudah masuk</p>
    </div>
    <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-brand-600 flex flex-col justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-500">Pendapatan Bulan Ini</p>
            <p class="mt-2.5 text-xl sm:text-2xl font-black text-brand-600">{{ rupiah($revenue['this_month']) }}</p>
        </div>
        <p class="mt-3 text-xs text-neutral-500">{{ now()->translatedFormat('F Y') }}</p>
    </div>
    <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-amber-500 flex flex-col justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-500">Piutang (Sisa Tagihan)</p>
            <p class="mt-2.5 text-xl sm:text-2xl font-black text-amber-600">{{ rupiah($revenue['receivable']) }}</p>
        </div>
        <p class="mt-3 text-xs text-neutral-500">Belum dibayar dari pesanan non-batal</p>
    </div>
</div>

{{-- 1. Pengeluaran Kas (Outflow Breakdown) --}}
<div class="mt-10 sm:mt-12 rounded-2xl border border-neutral-200 bg-white p-6 sm:p-7 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 sm:gap-6 pb-6 border-b border-neutral-100">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                <h2 class="text-sm font-extrabold text-ink uppercase tracking-wider">Struktur Pengeluaran Kas (Outflow Breakdown)</h2>
            </div>
            <p class="text-xs text-neutral-500 mt-2 leading-relaxed">Pemetaan seluruh uang kas keluar: HPP belanja pesanan, operasional konveksi, dan penarikan pribadi (prive).</p>
        </div>
        <div class="flex items-center gap-4 flex-wrap sm:flex-nowrap pt-1 lg:pt-0">
            <div class="flex items-center gap-6 bg-neutral-50/90 px-5 py-3 sm:px-6 sm:py-3.5 rounded-xl border border-neutral-200">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500 mb-1">Total Kas Keluar</p>
                    <p class="text-base sm:text-lg font-extrabold text-rose-700 leading-tight">{{ rupiah($financials['total_global_outflow']) }}</p>
                </div>
                <div class="h-9 w-px bg-neutral-200"></div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500 mb-1">Keluar Bulan Ini</p>
                    <p class="text-sm sm:text-base font-extrabold text-ink leading-tight">{{ rupiah($financials['this_month_global_outflow']) }}</p>
                </div>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('admin.expenses.index'))
            <a href="{{ route('admin.expenses.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-700 hover:underline px-2 py-1">
                <span>Kelola Beban</span>
                <span>&rarr;</span>
            </a>
            @endif
        </div>
    </div>

    {{-- Progress bar --}}
    @if($financials['total_global_outflow'] > 0)
    <div class="mt-6 mb-7">
        <div class="flex items-center justify-between text-xs font-bold text-neutral-500 mb-2.5">
            <span>Porsi Pengeluaran dari Total Kas Keluar:</span>
            <span>100% Kas Keluar Global</span>
        </div>
        <div class="h-3 w-full bg-neutral-100 rounded-full overflow-hidden flex shadow-inner">
            <div class="bg-blue-500 h-full transition-all duration-500" style="width: {{ $financials['hpp_share'] }}%" title="HPP Produksi: {{ $financials['hpp_share'] }}%"></div>
            <div class="bg-indigo-600 h-full transition-all duration-500" style="width: {{ $financials['convection_share'] }}%" title="Ops. Konveksi: {{ $financials['convection_share'] }}%"></div>
            <div class="bg-rose-500 h-full transition-all duration-500" style="width: {{ $financials['personal_share'] }}%" title="Ops. Pribadi / Prive: {{ $financials['personal_share'] }}%"></div>
        </div>
    </div>
    @endif

    {{-- 3 Pillars of Outflow --}}
    <div class="grid gap-5 sm:grid-cols-3">
        {{-- 1. HPP Produksi --}}
        <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-blue-500 bg-blue-50/15 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-600">Total HPP Produksi</span>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-blue-100 text-blue-700">
                        {{ $financials['hpp_share'] }}% Kas
                    </span>
                </div>
                <p class="mt-3 text-xl sm:text-2xl font-black text-blue-600">{{ rupiah($financials['total_hpp']) }}</p>
                <div class="mt-2 text-xs text-neutral-600">
                    <span>Bulan Ini: <strong class="text-ink font-bold">{{ rupiah($financials['this_month_hpp']) }}</strong></span>
                </div>
            </div>
            <p class="mt-5 pt-3.5 border-t border-neutral-100 text-xs text-neutral-500 leading-relaxed">
                Belanja kain, makloon, CMT, sablon &amp; bordir. Mengurangi laba kotor pesanan.
            </p>
        </div>

        {{-- 2. Ops. Konveksi --}}
        <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-indigo-600 bg-indigo-50/15 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-600">Ops. Konveksi</span>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">
                            {{ $financials['convection_share'] }}% Kas
                        </span>
                        <a href="{{ route('admin.expenses.index', ['expense_type' => 'convection']) }}" class="text-[10px] font-bold text-indigo-700 hover:underline">
                            Bisnis &rarr;
                        </a>
                    </div>
                </div>
                <p class="mt-3 text-xl sm:text-2xl font-black text-indigo-700">{{ rupiah($financials['convection_expenses_total']) }}</p>
                <div class="mt-2 text-xs text-neutral-600">
                    <span>Bulan Ini: <strong class="text-ink font-bold">{{ rupiah($financials['convection_expenses_this_month']) }}</strong></span>
                </div>
            </div>
            <p class="mt-5 pt-3.5 border-t border-neutral-100 text-xs text-neutral-500 leading-relaxed">
                Overhead workshop (listrik, sewa, gaji tim, servis mesin). Mengurangi laba bersih.
            </p>
        </div>

        {{-- 3. Ops. Pribadi --}}
        <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-rose-500 bg-rose-50/15 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-600">Ops. Pribadi</span>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-rose-100 text-rose-700">
                            {{ $financials['personal_share'] }}% Kas
                        </span>
                        <a href="{{ route('admin.expenses.index', ['expense_type' => 'personal']) }}" class="text-[10px] font-bold text-rose-700 hover:underline">
                            Prive &rarr;
                        </a>
                    </div>
                </div>
                <p class="mt-3 text-xl sm:text-2xl font-black text-rose-600">{{ rupiah($financials['personal_expenses_total']) }}</p>
                <div class="mt-2 text-xs text-neutral-600">
                    <span>Bulan Ini: <strong class="text-ink font-bold">{{ rupiah($financials['personal_expenses_this_month']) }}</strong></span>
                </div>
            </div>
            <p class="mt-5 pt-3.5 border-t border-neutral-100 text-xs text-neutral-500 leading-relaxed">
                Penarikan uang kas keperluan pribadi/keluarga owner. Mengurangi saldo kas riil.
            </p>
        </div>
    </div>
</div>

{{-- 2. Profitabilitas Perusahaan & Talangan Kas --}}
<div class="mt-12 sm:mt-14">
    <div class="mb-6 sm:mb-7">
        <h2 class="text-sm font-extrabold text-ink uppercase tracking-wider">Laba Perusahaan &amp; Beban Operasional</h2>
        <p class="text-xs text-neutral-500 mt-1.5 leading-relaxed">Perhitungan keuntungan riil setelah dikurangi biaya produksi (HPP) dan beban konveksi (OPEX).</p>
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        {{-- Laba Kotor Pesanan --}}
        <div class="admin-card !p-5 sm:!p-6 border-l-4 !border-l-emerald-500 bg-emerald-50/15 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-neutral-600">Laba Kotor Pesanan</p>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                        {{ $revenue['order_value'] > 0 ? round(($financials['gross_profit'] / $revenue['order_value']) * 100, 1) : 0 }}% Margin
                    </span>
                </div>
                <p class="mt-3 text-xl sm:text-2xl font-black text-emerald-600">{{ rupiah($financials['gross_profit']) }}</p>
                <p class="mt-2 text-xs text-neutral-600">Nilai Order − Total HPP</p>
            </div>
            <p class="mt-5 pt-3.5 border-t border-neutral-100 text-xs text-neutral-500 leading-relaxed">
                Keuntungan kotor langsung dari seluruh pesanan non-batal sebelum dipotong biaya operasional.
            </p>
        </div>

        {{-- Laba Bersih Bisnis --}}
        <div class="admin-card !p-5 sm:!p-6 border-l-4 {{ $financials['net_profit'] >= 0 ? '!border-l-teal-600 bg-teal-50/15' : '!border-l-red-500 bg-red-50/15' }} flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-neutral-600">Laba Bersih Bisnis</p>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded {{ $financials['net_profit'] >= 0 ? 'bg-teal-100 text-teal-800' : 'bg-red-100 text-red-800' }}">
                        {{ $revenue['order_value'] > 0 ? round(($financials['net_profit'] / $revenue['order_value']) * 100, 1) : 0 }}% Margin
                    </span>
                </div>
                <p class="mt-3 text-xl sm:text-2xl font-black {{ $financials['net_profit'] >= 0 ? 'text-teal-700' : 'text-red-600' }}">
                    {{ rupiah($financials['net_profit']) }}
                </p>
                <p class="mt-2 text-xs text-neutral-600">Laba Kotor − Ops. Konveksi</p>
            </div>
            <p class="mt-5 pt-3.5 border-t border-neutral-100 text-xs text-neutral-500 leading-relaxed">
                Keuntungan bersih operasional konveksi (beban pribadi owner tidak memotong laba ini).
            </p>
        </div>

        {{-- Talangan Kas (Nombok) --}}
        <div class="admin-card !p-5 sm:!p-6 border-l-4 {{ ($financials['total_cash_advances'] ?? 0) > 0 ? '!border-l-amber-500 bg-amber-50/20' : '!border-l-slate-400' }} flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-neutral-600">Talangan Kas (Nombok)</p>
                    @if(($financials['deficit_orders_count'] ?? 0) > 0)
                        <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="text-[10px] font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 px-2 py-0.5 rounded transition">
                            {{ $financials['deficit_orders_count'] }} Order &rarr;
                        </a>
                    @endif
                </div>
                <p class="mt-3 text-xl sm:text-2xl font-black {{ ($financials['total_cash_advances'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-700' }}">
                    {{ rupiah($financials['total_cash_advances'] ?? 0) }}
                </p>
                <p class="mt-2 text-xs text-neutral-600">
                    @if(($financials['total_cash_advances'] ?? 0) > 0)
                        Kas kantor menombok DP
                    @else
                        Semua belanja tertutup DP
                    @endif
                </p>
            </div>
            <p class="mt-5 pt-3.5 border-t border-neutral-100 text-xs text-neutral-500 leading-relaxed">
                Uang kas kantor yang saat ini terpakai belanja bahan karena DP pelanggan belum cukup.
            </p>
        </div>
    </div>
</div>

{{-- Action required --}}
<div class="mt-12 sm:mt-14 grid gap-6 xl:grid-cols-3">
    <div class="admin-card !p-5 sm:!p-6">
        <h2 class="font-extrabold text-ink text-base">Perlu Tindakan</h2>
        <ul class="mt-5 space-y-3.5 text-sm">
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

        <h2 class="mt-8 pt-6 border-t border-neutral-100 font-extrabold text-ink text-base">Inquiry Terbaru</h2>
        <ul class="mt-4 divide-y divide-line text-sm">
            @forelse($recentInquiries as $inquiry)
                <li>
                    <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="flex items-center justify-between gap-2 py-3 hover:text-brand-600">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">{{ $inquiry->name }}</span>
                            <span class="block truncate text-xs text-neutral-500">{{ $inquiry->service_name ?: 'Umum' }} &bull; {{ $inquiry->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $inquiry->status === 'new' ? 'bg-amber-100 text-amber-700' : 'bg-neutral-100 text-neutral-600' }}">{{ $inquiry->status_label }}</span>
                    </a>
                </li>
            @empty
                <li class="py-3 text-neutral-500">Belum ada inquiry.</li>
            @endforelse
        </ul>
    </div>

    {{-- Recent orders --}}
    <div class="admin-card xl:col-span-2 !p-5 sm:!p-6">
        <div class="flex items-center justify-between">
            <h2 class="font-extrabold text-ink text-base">Pesanan Terbaru</h2>
            <a href="{{ route('admin.orders.create') }}" class="btn-primary !px-4 !py-2 text-xs">+ Buat Pesanan</a>
        </div>
        <div class="mt-5 overflow-x-auto">
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
