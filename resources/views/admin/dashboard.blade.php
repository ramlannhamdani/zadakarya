@extends('layouts.admin')

@section('title', 'Ikhtisar Bisnis')

@section('content')
<!-- 1. TOP STATS BAR (Sleek Flat Container) -->
<section class="bg-white rounded-2xl p-6 shadow-sm">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
        <!-- Total Pesanan -->
        <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Pesanan</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-slate-900">{{ $stats['total_orders'] }}</span>
                <span class="text-[11px] text-slate-400">transaksi</span>
            </div>
        </div>

        <!-- Pesanan Aktif -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Pesanan Aktif</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-amber-600">{{ $stats['active_orders'] }}</span>
                <span class="text-[11px] text-amber-600/80 font-medium">sedang berjalan</span>
            </div>
        </div>

        <!-- Pesanan Selesai -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Pesanan Selesai</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-emerald-600">{{ $stats['completed_orders'] }}</span>
                <span class="text-[11px] text-emerald-600/80 font-medium">terkirim</span>
            </div>
        </div>

        <!-- Belum Lunas -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Belum Lunas</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-slate-700">{{ $stats['unpaid_orders'] }}</span>
                <span class="text-[11px] text-slate-400 font-medium">order DP / sisa</span>
            </div>
        </div>

        <!-- Inquiry Baru -->
        <div class="pt-4 sm:pt-0 sm:px-4 col-span-2 sm:col-span-1">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Inquiry Baru</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-slate-400">{{ $stats['new_inquiries'] }}</span>
                <span class="text-[11px] text-slate-400">calon klien</span>
            </div>
        </div>
    </div>
</section>

<!-- 2. FINANCIAL TILES (Soft Flat Surface, No Harsh Strokes) -->
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Nilai Pesanan</span>
            <h3 class="text-2xl font-bold text-slate-900 mt-2">{{ rupiah($revenue['order_value']) }}</h3>
        </div>
        <p class="text-xs text-slate-400 mt-4">Grand total semua pesanan non-batal</p>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Total Pendapatan Terbayar</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <h3 class="text-2xl font-bold text-emerald-600 mt-2">{{ rupiah($revenue['total']) }}</h3>
        </div>
        <p class="text-xs text-slate-400 mt-4">Seluruh pembayaran yang sudah masuk</p>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Pendapatan Bulan Ini</span>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-brand-50 text-brand-700">{{ now()->translatedFormat('F Y') }}</span>
            </div>
            <h3 class="text-2xl font-bold text-brand-700 mt-2">{{ rupiah($revenue['this_month']) }}</h3>
        </div>
        <p class="text-xs text-slate-400 mt-4">Penerimaan kas masuk bulan berjalan</p>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-600">Total Piutang Tagihan</span>
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            </div>
            <h3 class="text-2xl font-bold text-amber-600 mt-2">{{ rupiah($revenue['receivable']) }}</h3>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
            <a href="{{ route('admin.orders.index', ['filter' => 'ready_to_bill']) }}" class="text-rose-600 font-semibold hover:underline flex items-center gap-1" title="Pesanan selesai tapi belum lunas">
                <span>Siap Tagih:</span>
                <span>{{ rupiah($revenue['completed_receivable']) }}</span>
            </a>
            <span class="text-slate-400 font-medium" title="Pesanan aktif dalam produksi">WIP: {{ rupiah($revenue['active_receivable']) }}</span>
        </div>
    </div>
</section>

<!-- 2B. PETA ALOKASI DANA (DI MANA POSISI UANG SAAT INI?) -->
<section class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-5">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-brand-600"></span>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Peta Alokasi Dana (Di Mana Uang Berada?)</h3>
            </div>
            <p class="text-xs text-slate-400 mt-1">Pemetaan transparan posisi kas riil, uang tertahan menombok produksi, dan tagihan yang siap ditarik.</p>
        </div>
        <div class="flex items-center gap-3 self-start md:self-auto text-xs">
            <span class="text-slate-400">Total Piutang:</span>
            <span class="font-bold text-slate-800">{{ rupiah($revenue['receivable']) }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- 1. Kas Bebas Likuid -->
        <div class="bg-slate-50/80 hover:bg-slate-50 rounded-2xl p-5 flex flex-col justify-between transition-colors border border-transparent hover:border-slate-100">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">1. Kas Bebas Likuid</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $financials['current_cash_balance'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $financials['current_cash_balance'] >= 0 ? 'Tersedia' : 'Defisit Kas' }}
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-bold {{ $financials['current_cash_balance'] >= 0 ? 'text-slate-900' : 'text-rose-600' }} tracking-tight">
                    {{ rupiah($financials['current_cash_balance']) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1">Saldo Kas di Rekening / Laci</div>
            </div>
            <p class="text-[11px] text-slate-500 mt-4 leading-relaxed">
                Uang tunai &amp; bank yang siap dipakai tanpa terikat beban produksi yang belum dibayar.
            </p>
        </div>

        <!-- 2. Uang Tertahan di Produksi (Dana Talangan HPP) -->
        <div class="bg-amber-50/50 hover:bg-amber-50/80 rounded-2xl p-5 flex flex-col justify-between transition-colors border border-amber-100/50">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800">2. Talangan Kas HPP</span>
                    @if(($financials['deficit_orders_count'] ?? 0) > 0)
                        <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 hover:bg-amber-200 transition-colors">
                            {{ $financials['deficit_orders_count'] }} Order &rarr;
                        </a>
                    @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800">Rp0 Aman</span>
                    @endif
                </div>
                <div class="text-xl sm:text-2xl font-bold {{ ($financials['total_cash_advances'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }} tracking-tight">
                    {{ rupiah($financials['total_cash_advances'] ?? 0) }}
                </div>
                <div class="text-[11px] text-amber-700/80 mt-1">Nombok Belanja Produksi</div>
            </div>
            <p class="text-[11px] text-amber-900/70 mt-4 leading-relaxed">
                Uang kas yang menalangi belanja kain/makloon (HPP &gt; DP). Cair kembali saat customer bayar.
            </p>
        </div>

        <!-- 3. Piutang Siap Tagih (Pesanan Selesai) -->
        <div class="bg-rose-50/50 hover:bg-rose-50/80 rounded-2xl p-5 flex flex-col justify-between transition-colors border border-rose-100/60">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-rose-800">3. Siap Ditagih</span>
                    @if(($financials['completed_unpaid_count'] ?? 0) > 0)
                        <a href="{{ route('admin.orders.index', ['filter' => 'ready_to_bill']) }}" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-200 text-rose-900 hover:bg-rose-300 transition-colors">
                            🚨 {{ $financials['completed_unpaid_count'] }} Order
                        </a>
                    @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800">Lunas Semua</span>
                    @endif
                </div>
                <div class="text-xl sm:text-2xl font-bold text-rose-600 tracking-tight">
                    {{ rupiah($financials['completed_receivable'] ?? 0) }}
                </div>
                <div class="text-[11px] text-rose-700/80 mt-1">Pesanan Selesai Belum Lunas</div>
            </div>
            <p class="text-[11px] text-rose-900/70 mt-4 leading-relaxed">
                Barang sudah tuntas 100% &amp; siap kirim. Prioritas tagih utama via WA / Invoice pelunasan.
            </p>
        </div>

        <!-- 4. Piutang Pesanan Berjalan (WIP) -->
        <div class="bg-blue-50/40 hover:bg-blue-50/70 rounded-2xl p-5 flex flex-col justify-between transition-colors border border-blue-100/50">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800">4. Piutang Berjalan</span>
                    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors">
                        {{ $financials['active_unpaid_count'] ?? 0 }} Order
                    </a>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-blue-600 tracking-tight">
                    {{ rupiah($financials['active_receivable'] ?? 0) }}
                </div>
                <div class="text-[11px] text-blue-700/80 mt-1">Masih Dalam Pengerjaan</div>
            </div>
            <p class="text-[11px] text-blue-900/70 mt-4 leading-relaxed">
                Sisa tagihan pesanan aktif di workshop (potong/jahit/finishing). Ditagih saat barang selesai.
            </p>
        </div>

        <!-- 5. Kewajiban Pinjaman Pihak Luar -->
        <div class="bg-purple-50/40 hover:bg-purple-50/70 rounded-2xl p-5 flex flex-col justify-between transition-colors border border-purple-100/50">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-purple-800">5. Utang Luar</span>
                    <a href="{{ route('admin.loans.index') }}" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                        Kelola &rarr;
                    </a>
                </div>
                <div class="text-xl sm:text-2xl font-bold {{ ($financials['total_active_debt'] ?? 0) > 0 ? 'text-purple-700' : 'text-slate-900' }} tracking-tight">
                    {{ rupiah($financials['total_active_debt'] ?? 0) }}
                </div>
                <div class="text-[11px] text-purple-700/80 mt-1">Sisa Pokok Pinjaman Luar</div>
            </div>
            <p class="text-[11px] text-purple-900/70 mt-4 leading-relaxed">
                Pinjaman eksternal yang menambah saldo kas dan harus diangsur ke kreditur.
            </p>
        </div>
    </div>
</section>

<!-- 3. STRUKTUR PENGELUARAN KAS (Clean Soft Cards) -->
<section class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
        <div>
            <h3 class="text-base font-bold text-slate-900 tracking-tight">Struktur Pengeluaran Kas</h3>
            <p class="text-xs text-slate-400 mt-0.5">Pemetaan seluruh uang kas keluar: HPP belanja pesanan, operasional konveksi, dan penarikan pribadi (prive).</p>
        </div>
        <div class="flex items-center gap-6 self-start md:self-auto">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Kas Keluar</span>
                <span class="text-base font-bold text-rose-600">{{ rupiah($financials['total_global_outflow']) }}</span>
            </div>
            <div class="h-7 w-px bg-slate-100"></div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keluar Bulan Ini</span>
                <span class="text-base font-bold text-rose-600">{{ rupiah($financials['this_month_global_outflow']) }}</span>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('admin.expenses.index'))
            <a href="{{ route('admin.expenses.index') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition-colors flex items-center gap-1.5">
                <span>Kelola</span>
                <i class="fa-solid fa-chevron-right text-[9px]"></i>
            </a>
            @endif
        </div>
    </div>

    <!-- Minimalist Segmented Bar -->
    @if($financials['total_global_outflow'] > 0)
    <div class="my-6">
        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex gap-0.5">
            <div class="bg-rose-500 h-full rounded-l-full" style="width: {{ $financials['hpp_share'] }}%;" title="HPP Produksi ({{ $financials['hpp_share'] }}%)"></div>
            <div class="bg-rose-400 h-full" style="width: {{ $financials['convection_share'] }}%;" title="Ops Konveksi ({{ $financials['convection_share'] }}%)"></div>
            <div class="bg-rose-300 h-full rounded-r-full" style="width: {{ $financials['personal_share'] }}%;" title="Ops Pribadi ({{ $financials['personal_share'] }}%)"></div>
        </div>
        <div class="flex flex-wrap gap-6 mt-3 text-xs text-slate-500">
            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-500"></span><span>HPP Produksi ({{ $financials['hpp_share'] }}%)</span></div>
            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-400"></span><span>Ops Konveksi ({{ $financials['convection_share'] }}%)</span></div>
            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-300"></span><span>Ops Pribadi ({{ $financials['personal_share'] }}%)</span></div>
        </div>
    </div>
    @endif

    <!-- 3 Outflow Breakdown Columns -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 {{ $financials['total_global_outflow'] > 0 ? '' : 'mt-6' }}">
        <!-- HPP Produksi -->
        <div class="bg-slate-50/70 rounded-2xl p-5 flex flex-col justify-between">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xs font-semibold text-slate-700">Total HPP Produksi</span>
                <span class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">{{ $financials['hpp_share'] }}%</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-rose-600 tracking-tight">{{ rupiah($financials['total_hpp']) }}</div>
                <div class="text-xs text-slate-500 mt-1"><span class="text-slate-400">Bulan Ini:</span> <span class="font-medium text-rose-600">{{ rupiah($financials['this_month_hpp']) }}</span></div>
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Belanja kain, makloon, CMT, sablon & bordir. Mengurangi laba kotor pesanan.
            </p>
        </div>

        <!-- Ops Konveksi -->
        <div class="bg-slate-50/70 rounded-2xl p-5 flex flex-col justify-between">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xs font-semibold text-slate-700">Ops. Konveksi</span>
                <span class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">{{ $financials['convection_share'] }}%</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-rose-600 tracking-tight">{{ rupiah($financials['convection_expenses_total']) }}</div>
                <div class="text-xs text-slate-500 mt-1"><span class="text-slate-400">Bulan Ini:</span> <span class="font-medium text-rose-600">{{ rupiah($financials['convection_expenses_this_month']) }}</span></div>
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Overhead workshop (listrik, sewa, gaji tim, servis mesin). Mengurangi laba bersih.
            </p>
        </div>

        <!-- Ops Pribadi -->
        <div class="bg-slate-50/70 rounded-2xl p-5 flex flex-col justify-between">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xs font-semibold text-slate-700">Ops. Pribadi</span>
                <span class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">{{ $financials['personal_share'] }}%</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-rose-600 tracking-tight">{{ rupiah($financials['personal_expenses_total']) }}</div>
                <div class="text-xs text-slate-500 mt-1"><span class="text-slate-400">Bulan Ini:</span> <span class="font-medium text-rose-600">{{ rupiah($financials['personal_expenses_this_month']) }}</span></div>
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Penarikan uang kas keperluan pribadi/keluarga owner. Mengurangi saldo kas riil.
            </p>
        </div>
    </div>
</section>

<!-- 4. LABA PERUSAHAAN & BEBAN OPERASIONAL (Soft Clean Surfaces) -->
<section class="space-y-4">
    <div>
        <h3 class="text-base font-bold text-slate-900 tracking-tight">Laba Perusahaan &amp; Beban Operasional</h3>
        <p class="text-xs text-slate-400 mt-0.5">Perhitungan keuntungan riil setelah dikurangi biaya produksi (HPP) dan beban konveksi (OPEX).</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Laba Kotor Pesanan -->
        <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-semibold text-slate-600">Laba Kotor Pesanan</span>
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">
                    {{ $revenue['order_value'] > 0 ? round(($financials['gross_profit'] / $revenue['order_value']) * 100, 1) : 0 }}% Margin
                </span>
            </div>
            <div>
                <div class="text-2xl font-bold text-emerald-600">{{ rupiah($financials['gross_profit']) }}</div>
                <div class="text-xs text-slate-400 mt-0.5">Nilai Order — Total HPP</div>
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Keuntungan kotor langsung dari seluruh pesanan non-batal sebelum dipotong biaya operasional.
            </p>
        </div>

        <!-- Laba Bersih Bisnis -->
        <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-semibold text-slate-600">Laba Bersih Bisnis</span>
                <span class="text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">
                    {{ $revenue['order_value'] > 0 ? round(($financials['net_profit'] / $revenue['order_value']) * 100, 1) : 0 }}% Margin
                </span>
            </div>
            <div>
                <div class="text-2xl font-bold {{ $financials['net_profit'] >= 0 ? 'text-slate-900' : 'text-rose-600' }}">{{ rupiah($financials['net_profit']) }}</div>
                <div class="text-xs text-slate-400 mt-0.5">Laba Kotor — Ops. Konveksi</div>
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Keuntungan bersih operasional konveksi (beban pribadi owner tidak memotong laba ini).
            </p>
        </div>

        <!-- Talangan Kas -->
        <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-semibold text-slate-600">Talangan Kas (Nombok)</span>
                @if(($financials['deficit_orders_count'] ?? 0) > 0)
                    <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 px-2 py-0.5 rounded-md transition-colors">
                        {{ $financials['deficit_orders_count'] }} Order &rarr;
                    </a>
                @else
                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">Aman</span>
                @endif
            </div>
            <div>
                <div class="text-2xl font-bold {{ ($financials['total_cash_advances'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ rupiah($financials['total_cash_advances'] ?? 0) }}</div>
                <div class="text-xs text-slate-400 mt-0.5">Kas kantor menombok DP</div>
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Uang kas kantor yang saat ini terpakai belanja bahan karena DP pelanggan belum cukup.
            </p>
        </div>

        <!-- Hutang / Pinjaman -->
        <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-semibold text-slate-600">Pinjaman (Pihak Luar)</span>
                <a href="{{ route('admin.loans.index') }}" class="text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 px-2 py-0.5 rounded-md transition-colors">
                    Kelola &rarr;
                </a>
            </div>
            <div>
                <div class="text-2xl font-bold {{ ($financials['total_active_debt'] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ rupiah($financials['total_active_debt'] ?? 0) }}</div>
                <div class="text-xs text-slate-400 mt-0.5">Sisa pokok utang pinjaman</div>
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Pinjaman dana dari kreditur/pihak lain yang menambah kas kantor dan wajib dicicil.
            </p>
        </div>
    </div>
</section>

<!-- 5. PERLU TINDAKAN & TABEL PESANAN TERBARU -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <!-- LEFT SIDE: ACTIONS & INQUIRY (4 COLS) -->
    <div class="lg:col-span-4 space-y-4">
        <!-- Perlu Tindakan -->
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h4 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-bell text-amber-500 text-xs"></i>
                <span>Perlu Tindakan</span>
            </h4>

            <div class="space-y-2">
                @if($actionRequired['awaiting_dp'] > 0)
                <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="p-3.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 transition-colors flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span class="font-medium text-slate-700">{{ $actionRequired['awaiting_dp'] }} pesanan belum menerima DP</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
                </a>
                @endif

                @if($actionRequired['near_deadline'] > 0)
                <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="p-3.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 transition-colors flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span class="font-medium text-slate-700">{{ $actionRequired['near_deadline'] }} pesanan mendekati deadline (≤7 hari)</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
                </a>
                @endif

                @if(($actionRequired['cash_deficits'] ?? 0) > 0)
                <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="p-3.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 transition-colors block text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-medium text-slate-800">{{ $actionRequired['cash_deficits'] }} pesanan nombok kas</span>
                        <span class="font-bold text-amber-600">{{ rupiah($actionRequired['cash_deficits_amount'] ?? 0) }}</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Belanja HPP > DP masuk • kas kantor menalangi</p>
                </a>
                @endif

                @if(($actionRequired['completed_unpaid'] ?? 0) > 0)
                <a href="{{ route('admin.orders.index', ['filter' => 'ready_to_bill']) }}" class="p-3.5 rounded-xl bg-rose-50/80 hover:bg-rose-100/80 transition-colors block text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-rose-800 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                            {{ $actionRequired['completed_unpaid'] }} pesanan selesai siap ditagih!
                        </span>
                        <span class="font-bold text-rose-700">{{ rupiah($actionRequired['completed_receivable'] ?? 0) }}</span>
                    </div>
                    <p class="text-[11px] text-rose-600/90">Produksi tuntas tapi belum lunas • Segera push penagihan</p>
                </a>
                @endif

                @if($actionRequired['new_inquiries'] > 0)
                <a href="{{ route('admin.inquiries.index', ['status' => 'new']) }}" class="p-3.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 transition-colors flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <span class="font-medium text-slate-700">{{ $actionRequired['new_inquiries'] }} inquiry baru belum ditindaklanjuti</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
                </a>
                @endif

                @if(array_sum(array_filter($actionRequired, fn ($k) => !in_array($k, ['cash_deficits_amount', 'completed_receivable']), ARRAY_FILTER_USE_KEY)) === 0)
                <div class="p-3.5 rounded-xl bg-slate-50 text-slate-500 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-check text-emerald-500"></i>
                    <span>Semua beres — tidak ada tindakan mendesak.</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Inquiry Terbaru -->
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h4 class="text-sm font-bold text-slate-900 mb-3">Inquiry Terbaru</h4>
            <div class="space-y-2.5">
                @forelse($recentInquiries as $inquiry)
                <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="p-3.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 transition-colors flex items-center justify-between block">
                    <div>
                        <h5 class="text-xs font-bold text-slate-900">{{ $inquiry->name }}</h5>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $inquiry->service_name ?: 'Umum' }} • {{ $inquiry->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="text-[11px] font-medium text-slate-600 bg-white px-2.5 py-1 rounded-lg shadow-xs">
                        {{ $inquiry->status_label }}
                    </span>
                </a>
                @empty
                <p class="text-xs text-slate-400 py-2">Belum ada inquiry masuk.</p>
                @endforelse
            </div>
        </div>

        <!-- Outflow Mini Donut Visual -->
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Porsi Beban Kas</h4>
            <div class="relative h-40 flex items-center justify-center">
                <canvas id="outflowChart"></canvas>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: ORDER TABLE (8 COLS, Clean & Borderless Look) -->
    <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-50">
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Pesanan Terbaru</h3>
                <p class="text-xs text-slate-400 mt-0.5">Daftar order aktif dan progress pengerjaan konveksi</p>
            </div>
            <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-xs active:scale-95">
                <i class="fa-solid fa-plus text-[10px]"></i>
                <span>Buat Pesanan</span>
            </a>
        </div>

        <!-- Table Responsive Wrapper -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                        <th class="py-3 px-4 font-normal">NO. ORDER</th>
                        <th class="py-3 px-4 font-normal">CUSTOMER</th>
                        <th class="py-3 px-4 font-normal text-right">TOTAL</th>
                        <th class="py-3 px-4 font-normal text-center">PEMBAYARAN</th>
                        <th class="py-3 px-4 font-normal">TAHAP</th>
                        <th class="py-3 px-4 font-normal text-right">DEADLINE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($recentOrders as $order)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3.5 px-4 font-medium text-brand-700 font-mono">
                            <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">{{ $order->order_number }}</a>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900">{{ $order->customer->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $order->items->first()?->product_name }}{{ $order->items->count() > 1 ? ' +'.($order->items->count()-1) : '' }}</div>
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-slate-900 text-right">{{ rupiah($order->grand_total) }}</td>
                        <td class="py-3.5 px-4 text-center">
                            @if($order->payment_status === 'paid')
                                <span class="inline-block px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-semibold text-[11px]">Lunas</span>
                            @elseif($order->payment_status === 'dp')
                                <span class="inline-block px-2.5 py-0.5 rounded-md bg-amber-50 text-amber-700 font-semibold text-[11px]">DP</span>
                            @else
                                <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium text-[11px]">Belum Dibayar</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">{{ $order->current_stage_name }}</td>
                        <td class="py-3.5 px-4 text-right {{ $order->deadline && $order->deadline->isPast() ? 'text-rose-500 font-semibold' : 'text-slate-500' }}">
                            {{ $order->deadline?->format('d/m/Y') ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">Belum ada pesanan terbaru.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('outflowChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['HPP Produksi', 'Ops Konveksi', 'Ops Pribadi'],
                    datasets: [{
                        data: [
                            {{ (float)($financials['total_hpp'] ?? 0) }},
                            {{ (float)($financials['convection_expenses_total'] ?? 0) }},
                            {{ (float)($financials['personal_expenses_total'] ?? 0) }}
                        ],
                        backgroundColor: ['#e11d48', '#fb7185', '#fda4af'],
                        borderWidth: 0,
                        hoverOffset: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let val = context.raw || 0;
                                    return ' ' + label + ': Rp ' + Number(val).toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    cutout: '78%'
                }
            });
        }
    });
</script>
@endpush
