@extends('layouts.admin')

@section('title', 'Dashboard Keuangan')

@section('header-actions')
<div class="hidden md:flex items-center gap-2">
    <a href="{{ route('admin.finance.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-all">
        <i class="fa-solid fa-scale-balanced text-[11px]"></i>
        <span class="hidden sm:inline">Neraca Skontro</span>
        <span class="sm:hidden">Neraca</span>
    </a>
    <a href="{{ route('admin.assets.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-all">
        <i class="fa-solid fa-boxes-stacked text-[11px]"></i>
        <span class="hidden sm:inline">Inventaris Aset</span>
        <span class="sm:hidden">Aset</span>
    </a>
    <a href="{{ route('admin.spreadsheet.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs font-semibold transition-all">
        <i class="fa-solid fa-file-excel text-[11px]"></i>
        <span>Spreadsheet</span>
    </a>
</div>
@endsection

@section('content')
<div class="min-w-0 space-y-6">

    <!-- ========================================================================= -->
    <!-- BAGIAN ATAS: DASHBOARD KEUANGAN — PEMETAAN UANG (16 KARTU METRIK)          -->
    <!-- Referensi Desain: Format & Layout Dashboard Keuangan - Zada Karya (Gambar 2)-->
    <!-- ========================================================================= -->
    <section class="space-y-4">
        <!-- Dashboard Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-brand-700"></span>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">DASHBOARD KEUANGAN - ZADA KARYA</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Daftar Ringkasan Metrik Keuangan · Pemetaan Posisi Kas, Piutang, Biaya &amp; Valuasi</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-medium flex items-center gap-1.5">
                    <i class="fa-regular fa-calendar text-[10px]"></i>
                    {{ now()->translatedFormat('d F Y') }}
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200/60">
                    <i class="fa-solid fa-circle-check text-[9px]"></i> Data Terkini
                </span>
            </div>
        </div>

        <!-- 16 Metric Cards Grid (4 Rows x 4 Columns) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">

            <!-- ===================== BARIS 1: KAS UTAMA & KEWAJIBAN ===================== -->

            <!-- 1. KAS RIIL -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-800 text-white px-4 py-2.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold tracking-wider uppercase">KAS RIIL</span>
                    <i class="fa-solid fa-wallet text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-slate-900 tracking-tight">
                        {{ rupiah($financials['current_cash_balance']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Saldo Kas di Bank &amp; Laci</span>
                        <span class="text-slate-400 font-mono text-[10px]">Likuid</span>
                    </div>
                </div>
            </div>

            <!-- 2. TALANGAN KAS (NOMBOK DP) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-800 text-white px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold tracking-wider uppercase">TALANGAN KAS</span>
                    </div>
                    <i class="fa-solid fa-hand-holding-dollar text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words {{ $financials['total_cash_advances'] > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">
                        {{ rupiah($financials['total_cash_advances']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        @if($financials['deficit_orders_count'] > 0)
                            <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="text-rose-600 font-semibold hover:underline flex items-center gap-1">
                                <span>{{ $financials['deficit_orders_count'] }} Order Nombok</span>
                                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                            </a>
                        @else
                            <span class="text-emerald-600 font-semibold">Kas Aman (Rp0 Nombok)</span>
                        @endif
                        <span class="text-slate-400 font-mono text-[10px]">Talangan Kas (Nombok)</span>
                    </div>
                </div>
            </div>

            <!-- 3. SISA PINJAMAN -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-800 text-white px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold tracking-wider uppercase">SISA PINJAMAN</span>
                    </div>
                    <i class="fa-solid fa-handshake text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words {{ $financials['total_active_debt'] > 0 ? 'text-purple-700' : 'text-slate-900' }} tracking-tight">
                        {{ rupiah($financials['total_active_debt']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <a href="{{ route('admin.loans.index') }}" class="text-purple-700 font-semibold hover:underline flex items-center gap-1">
                            <span>Utang Luar Kreditur</span>
                            <i class="fa-solid fa-chevron-right text-[8px]"></i>
                        </a>
                        <span class="text-slate-400 font-mono text-[10px]">Kewajiban</span>
                    </div>
                </div>
            </div>

            <!-- 4. KAS BERSIH (EMERALD ACCENT CARD) -->
            <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl shadow-sm text-white overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-emerald-800/60 px-4 py-2.5 flex items-center justify-between border-b border-emerald-500/30">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold tracking-wider uppercase">KAS BERSIH</span>
                    </div>
                    <i class="fa-solid fa-shield-halved text-emerald-200 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-white tracking-tight">
                        {{ rupiah($financials['kas_bersih']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-emerald-100/90 flex items-center justify-between">
                        <span>Kas Riil − Talangan (Estimasi)</span>
                        <span class="text-emerald-200 font-mono text-[10px]">Murni</span>
                    </div>
                </div>
            </div>

            <!-- ===================== BARIS 2: ARUS KAS & PIUTANG ===================== -->

            <!-- 5. KAS MASUK BLN INI -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold tracking-wider uppercase">KAS MASUK BLN INI</span>
                    </div>
                    <i class="fa-solid fa-arrow-down-left text-emerald-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-slate-900 tracking-tight">
                        {{ rupiah($financials['this_month_cash_inflow']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>{{ now()->translatedFormat('F Y') }}</span>
                        <span class="text-emerald-600 font-bold text-[10px]">Masuk</span>
                    </div>
                </div>
            </div>

            <!-- 6. KAS KELUAR BLN INI -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold tracking-wider uppercase">KAS KELUAR BLN INI</span>
                    <i class="fa-solid fa-arrow-up-right text-rose-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-rose-600 tracking-tight">
                        {{ rupiah($financials['this_month_global_outflow']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>HPP + Ops + Prive + Cicilan</span>
                        <span class="text-rose-600 font-bold text-[10px]">Keluar</span>
                    </div>
                </div>
            </div>

            <!-- 7. PIUTANG JT (JATUH TEMPO) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold tracking-wider uppercase">PIUTANG JT (JATUH TEMPO)</span>
                    </div>
                    <i class="fa-solid fa-clock-rotate-left text-amber-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words {{ $financials['critical_receivable'] > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">
                        {{ rupiah($financials['critical_receivable']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <a href="{{ route('admin.orders.index', ['filter' => 'ready_to_bill']) }}" class="text-rose-600 font-semibold hover:underline">
                            Selesai &amp; Lewat Deadline
                        </a>
                        <span class="text-rose-500 font-mono text-[10px]">Siap Tagih</span>
                    </div>
                </div>
            </div>

            <!-- 8. PIUTANG BERJALAN -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold tracking-wider uppercase">PIUTANG BERJALAN</span>
                    </div>
                    <i class="fa-solid fa-file-invoice-dollar text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-blue-600 tracking-tight">
                        {{ rupiah($financials['active_receivable']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Pesanan belum jatuh tempo</span>
                        <span class="text-blue-500 font-mono text-[10px]">WIP</span>
                    </div>
                </div>
            </div>

            <!-- ===================== BARIS 3: OPERASIONAL & PROFITABILITAS ===================== -->

            <!-- 9. TOTAL NILAI ORDER -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold tracking-wider uppercase">TOTAL NILAI ORDER</span>
                    </div>
                    <i class="fa-solid fa-cart-shopping text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-slate-900 tracking-tight">
                        {{ rupiah($revenue['order_value']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Grand Total Semua Order</span>
                        <span class="text-slate-400 font-mono text-[10px]">Omset</span>
                    </div>
                </div>
            </div>

            <!-- 10. BIAYA OPERASIONAL (MULTI-ROW) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold tracking-wider uppercase">BIAYA OPERASIONAL</span>
                    <i class="fa-solid fa-calculator text-slate-300 text-xs"></i>
                </div>
                <div class="p-3.5 sm:p-4 text-xs space-y-1.5">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>HPP:</span>
                        <span class="font-bold text-slate-900 font-mono">{{ rupiah($financials['total_hpp']) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Ops. Konveksi:</span>
                        <span class="font-bold text-slate-900 font-mono">{{ rupiah($financials['convection_expenses_total']) }}</span>
                    </div>
                    <div class="pt-1.5 border-t border-slate-100 flex justify-between items-center text-rose-600 font-bold">
                        <span>TOTAL:</span>
                        <span class="font-black text-sm font-mono">{{ rupiah($financials['total_hpp'] + $financials['convection_expenses_total']) }}</span>
                    </div>
                </div>
            </div>

            <!-- 11. LABA BERSIH & MARGIN (MULTI-ROW) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold tracking-wider uppercase">LABA BERSIH &amp; MARGIN</span>
                    <i class="fa-solid fa-chart-pie text-slate-300 text-xs"></i>
                </div>
                <div class="p-3.5 sm:p-4 text-xs space-y-1.5">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Laba Bersih:</span>
                        <span class="font-black {{ $financials['net_profit'] < 0 ? 'text-rose-600' : 'text-emerald-600' }} font-mono text-sm whitespace-nowrap">{{ rupiah($financials['net_profit']) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>% Margin:</span>
                        <span class="font-black {{ $financials['net_profit'] < 0 ? 'text-rose-700 bg-rose-50 border-rose-200/60' : 'text-emerald-700 bg-emerald-50 border-emerald-200/60' }} px-2 py-0.5 rounded-md border font-mono text-xs">
                            {{ $financials['profit_margin_pct'] }}%
                        </span>
                    </div>
                    <div class="pt-1.5 border-t border-slate-100 flex justify-between items-center text-[10px] text-slate-400">
                        <span>Laba Kotor Pesanan: {{ rupiah($financials['gross_profit']) }}</span>
                    </div>
                </div>
            </div>

            <!-- 12. ALOKASI LABA (NAVY SOLID CARD) -->
            <div class="bg-slate-900 text-white rounded-2xl shadow-xs border border-slate-800 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-950 px-4 py-2.5 flex items-center justify-between border-b border-slate-800">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-200">ALOKASI LABA</span>
                    <i class="fa-solid fa-scale-balanced text-amber-400 text-xs"></i>
                </div>
                <div class="p-3.5 sm:p-4 text-xs space-y-1.5">
                    <div class="flex justify-between items-center text-slate-300">
                        <span>Beban Pribadi (Prive):</span>
                        <span class="font-bold text-rose-400 font-mono">{{ rupiah($financials['personal_expenses_total']) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span>Sisa Laba:</span>
                        <span class="font-black {{ $financials['retained_earnings'] < 0 ? 'text-rose-400' : 'text-emerald-400' }} font-mono text-sm whitespace-nowrap">{{ rupiah($financials['retained_earnings']) }}</span>
                    </div>
                    <div class="pt-1.5 border-t border-slate-800 flex justify-between items-center text-[10px] text-slate-400">
                        <span>Laba Ditahan untuk Usaha</span>
                    </div>
                </div>
            </div>

            <!-- ===================== BARIS 4: ASET & TOTAL EKUITAS ===================== -->

            <!-- 13. ASET MESIN -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold tracking-wider uppercase">ASET MESIN</span>
                    <i class="fa-solid fa-scissors text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-slate-900 tracking-tight">
                        {{ rupiah($financials['aset_mesin']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Jahit, Obras, Bordir &amp; Steam</span>
                        <span class="text-indigo-600 font-mono text-[10px]">Produksi</span>
                    </div>
                </div>
            </div>

            <!-- 14. ASET LAIN -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold tracking-wider uppercase">ASET LAIN</span>
                    <i class="fa-solid fa-laptop text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-slate-900 tracking-tight">
                        {{ rupiah($financials['aset_lain']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Komputer, Kendaraan &amp; Kantor</span>
                        <span class="text-slate-400 font-mono text-[10px]">Fasilitas</span>
                    </div>
                </div>
            </div>

            <!-- 15. TOTAL ASET & INVESTASI -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-slate-700 text-white px-4 py-2.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold tracking-wider uppercase">TOTAL ASET &amp; INVESTASI</span>
                    <i class="fa-solid fa-boxes-stacked text-slate-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-slate-900 tracking-tight">
                        {{ rupiah($financials['total_aset_investasi']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Nilai Buku Seluruh Aset Tetap</span>
                        <a href="{{ route('admin.assets.index') }}" class="text-brand-600 font-bold text-[10px] hover:underline">Kelola &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- 16. TOTAL EQUITY (TEAL ACCENT CARD) -->
            <div class="bg-gradient-to-br from-teal-700 to-cyan-900 rounded-2xl shadow-sm text-white overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="bg-teal-900/60 px-4 py-2.5 flex items-center justify-between border-b border-teal-500/30">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-cyan-200">TOTAL EKUITAS</span>
                    <i class="fa-solid fa-gem text-cyan-300 text-xs"></i>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="text-lg xl:text-2xl font-black tabular-nums break-words text-white tracking-tight">
                        {{ rupiah($financials['total_equity']) }}
                    </div>
                    <div class="mt-2 text-[11px] text-cyan-100/90 flex items-center justify-between">
                        <span>Valuasi Bersih (Aktiva - Pasiva)</span>
                        <span class="text-cyan-200 font-mono text-[10px]">Kekayaan</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- BAGIAN BAWAH: RINCIAN & TREN KEUANGAN OPERASIONAL (2 KOLOM SEIMBANG)       -->
    <!-- Referensi Desain: Dashboard Keuangan Zada Karya - Developer Preview (Gbr 3)-->
    <!-- ========================================================================= -->
    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">

        <!-- ======================= KOLOM KIRI ======================= -->
        <div class="min-w-0 space-y-6">

            <!-- 1. TABEL: PIUTANG KRITIS & JATUH TEMPO -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-400 text-xs"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider">PIUTANG KRITIS &amp; JATUH TEMPO</h3>
                    </div>
                    <a href="{{ route('admin.orders.index', ['filter' => 'ready_to_bill']) }}" class="text-[11px] text-slate-300 hover:text-white font-medium hover:underline">
                        Lihat Semua &rarr;
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100 text-[11px]">
                            <tr>
                                <th class="py-2.5 px-4 font-semibold">No. Order</th>
                                <th class="py-2.5 px-4 font-semibold">Pelanggan</th>
                                <th class="py-2.5 px-4 font-semibold text-right">Nilai Tagihan</th>
                                <th class="py-2.5 px-4 font-semibold text-center">Jatuh Tempo</th>
                                <th class="py-2.5 px-4 font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($criticalOrders as $order)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2.5 px-4 font-mono font-bold text-brand-700">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">{{ $order->order_number }}</a>
                                </td>
                                <td class="py-2.5 px-4 font-medium text-slate-800 max-w-[140px] truncate" title="{{ $order->customer?->name }}">
                                    {{ $order->customer?->name ?? 'Pelanggan' }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-bold text-rose-600 font-mono">
                                    {{ rupiah($order->remaining) }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-500 {{ $order->deadline && $order->deadline->isPast() ? 'text-rose-600 font-semibold' : '' }}">
                                    {{ $order->deadline?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    @if($order->status === 'completed')
                                        <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200/60">Siap Tagih</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px] border border-amber-200/60">Belum lunas</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mb-1 block text-base"></i>
                                    Tidak ada piutang jatuh tempo. Seluruh tagihan aman terkendali.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-800 text-white font-bold text-xs">
                            <tr>
                                <td class="py-2.5 px-4 font-bold" colspan="2">TOTAL SELURUH PIUTANG KRITIS</td>
                                <td class="py-2.5 px-4 text-right font-mono font-black text-white">
                                    {{ rupiah($financials['critical_receivable']) }}
                                </td>
                                <td class="py-2.5 px-4" colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- 2. TABEL: KOMPOSISI BIAYA PRODUKSI (HPP) & OPS -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-chart-simple text-blue-400 text-xs"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider">KOMPOSISI BIAYA PRODUKSI (HPP) &amp; OPS</h3>
                    </div>
                    <span class="text-[11px] text-slate-300 font-mono">Porsi Belanja</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100 text-[11px]">
                            <tr>
                                <th class="py-2.5 px-4 font-semibold">Kategori HPP</th>
                                <th class="py-2.5 px-4 font-semibold text-right">Total Biaya</th>
                                <th class="py-2.5 px-4 font-semibold text-left w-44">Porsi Biaya</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($costComposition as $costItem)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2 px-4 font-medium text-slate-800">{{ $costItem['name'] }}</td>
                                <td class="py-2 px-4 text-right font-mono text-slate-900 font-bold">{{ rupiah($costItem['amount']) }}</td>
                                <td class="py-2 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="bg-blue-600 h-full rounded-full transition-all" style="width: {{ $costItem['percentage'] }}%;"></div>
                                        </div>
                                        <span class="font-mono text-[11px] font-semibold text-slate-600 w-12 text-right">{{ $costItem['percentage'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-800 text-white font-bold text-xs">
                            <tr>
                                <td class="py-2.5 px-4 font-bold">TOTAL</td>
                                <td class="py-2.5 px-4 text-right font-mono font-black text-white">
                                    {{ rupiah($financials['total_hpp']) }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono">{{ $financials['total_hpp'] > 0 ? '100.0%' : '0.0%' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- 3. GRAFIK: TREN ARUS KAS BULANAN -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Tren Arus Kas Bulanan</h3>
                    <div class="flex items-center gap-4 text-xs font-medium">
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <span class="w-3 h-3 rounded-sm bg-emerald-600"></span>
                            <span>Kas Masuk</span>
                        </span>
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <span class="w-3 h-3 rounded-sm bg-rose-500"></span>
                            <span>Kas Keluar</span>
                        </span>
                    </div>
                </div>
                <div class="relative h-60 w-full">
                    <canvas id="cashflowBarChart"></canvas>
                </div>
            </div>

        </div>

        <!-- ======================= KOLOM KANAN ======================= -->
        <div class="min-w-0 space-y-6">

            <!-- 4. TABEL: STATUS ORDER BERJALAN (WIP) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-spinner text-teal-300 text-xs"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider">STATUS ORDER BERJALAN (WIP)</h3>
                    </div>
                    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="text-[11px] text-slate-300 hover:text-white font-medium hover:underline">
                        Lihat Semua &rarr;
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100 text-[11px]">
                            <tr>
                                <th class="py-2.5 px-4 font-semibold">No. Order</th>
                                <th class="py-2.5 px-4 font-semibold">Pelanggan</th>
                                <th class="py-2.5 px-4 font-semibold text-center">Progres Produksi</th>
                                <th class="py-2.5 px-4 font-semibold text-right">Nilai Pelunasan</th>
                                <th class="py-2.5 px-4 font-semibold text-center">Status Tahap</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($wipOrders as $wip)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2.5 px-4 font-mono font-bold text-brand-700">
                                    <a href="{{ route('admin.orders.show', $wip['order']) }}" class="hover:underline">{{ $wip['order_number'] }}</a>
                                </td>
                                <td class="py-2.5 px-4 font-medium text-slate-800 max-w-[130px] truncate" title="{{ $wip['customer'] }}">
                                    {{ $wip['customer'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-600 text-white shadow-2xs">
                                        {{ $wip['progress_label'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">
                                    {{ rupiah($wip['pelunasan']) }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-600 text-[11px]">
                                    {{ $wip['stage_name'] }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">
                                    Tidak ada order dalam pengerjaan saat ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-800 text-white font-bold text-xs">
                            <tr>
                                <td class="py-2.5 px-4 font-bold" colspan="3">TOTAL SELURUH WIP</td>
                                <td class="py-2.5 px-4 text-right font-mono font-black text-white">
                                    {{ rupiah($financials['total_wip_pelunasan']) }}
                                </td>
                                <td class="py-2.5 px-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- 5. TABEL: BEBAN PRIBADI PER BULAN (OWNER / PRIVE) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-user-tie text-purple-300 text-xs"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider">BEBAN PRIBADI PER BULAN (OWNER / PRIVE)</h3>
                    </div>
                    <span class="text-[11px] text-slate-300 font-mono">{{ now()->translatedFormat('F Y') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100 text-[11px]">
                            <tr>
                                <th class="py-2.5 px-4 font-semibold">No. Transaksi</th>
                                <th class="py-2.5 px-4 font-semibold">Tanggal</th>
                                <th class="py-2.5 px-4 font-semibold">Deskripsi Pengeluaran</th>
                                <th class="py-2.5 px-4 font-semibold">Kategori</th>
                                <th class="py-2.5 px-4 font-semibold text-right">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($personalExpensesList as $prive)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2 px-4 font-mono font-semibold text-purple-700">
                                    {{ $prive->reference_no }}
                                </td>
                                <td class="py-2 px-4 text-slate-500 font-medium">
                                    {{ $prive->expense_date?->format('d M') ?? '—' }}
                                </td>
                                <td class="py-2 px-4 font-medium text-slate-800 max-w-[140px] truncate" title="{{ $prive->title }}">
                                    {{ $prive->title }}
                                </td>
                                <td class="py-2 px-4 text-slate-600">
                                    <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-[10px] font-semibold">
                                        {{ $prive->category_label }}
                                    </span>
                                </td>
                                <td class="py-2 px-4 text-right font-mono font-bold text-rose-600">
                                    {{ rupiah($prive->amount) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-6 px-4 text-center text-slate-400 whitespace-normal">
                                    Belum ada catatan beban pribadi bulan ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-800 text-white font-bold text-xs">
                            <tr>
                                <td class="py-2.5 px-4 font-bold" colspan="4">TOTAL BULAN INI</td>
                                <td class="py-2.5 px-4 text-right font-mono font-black text-white">
                                    {{ rupiah($financials['total_prive_month']) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- 6. GRAFIK: TREN LABA BERSIH & ESTIMASI LABA -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Tren Laba Bersih &amp; Estimasi Laba</h3>
                    <div class="flex items-center gap-4 text-xs font-medium">
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <span class="w-3 h-0.5 bg-blue-500"></span>
                            <span>Estimasi Laba Kotor</span>
                        </span>
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <span class="w-3 h-0.5 bg-emerald-500"></span>
                            <span>Laba Bersih</span>
                        </span>
                    </div>
                </div>
                <p class="mb-3 text-[11px] text-slate-500">Estimasi berdasarkan nilai order dan HPP yang dicatat per bulan; laba bersih dikurangi ops. konveksi. Prive terpisah.</p>
                <div class="relative h-60 w-full">
                    <canvas id="profitLineChart"></canvas>
                </div>
            </div>

        </div>

    </section>

    <!-- ========================================================================= -->
    <!-- BAGIAN OPERASIONAL: TINDAKAN DIPERLUKAN & PESANAN TERBARU                  -->
    <!-- ========================================================================= -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start pt-2">

        <!-- Tindakan Mendesak & Inquiries (4 Kolom) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-bell text-amber-500 text-xs"></i>
                    <span>Perlu Tindakan</span>
                </h4>

                <div class="space-y-2">
                    @if($actionRequired['awaiting_dp'] > 0)
                    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <span class="font-medium text-slate-700">{{ $actionRequired['awaiting_dp'] }} pesanan belum DP</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-400 text-[9px]"></i>
                    </a>
                    @endif

                    @if($actionRequired['near_deadline'] > 0)
                    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span class="font-medium text-slate-700">{{ $actionRequired['near_deadline'] }} pesanan deadline &le;7 hari</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-400 text-[9px]"></i>
                    </a>
                    @endif

                    @if(($actionRequired['cash_deficits'] ?? 0) > 0)
                    <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="p-3 rounded-xl bg-rose-50/60 hover:bg-rose-100/60 transition-colors block text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-rose-800">{{ $actionRequired['cash_deficits'] }} order nombok kas</span>
                            <span class="font-bold text-rose-700 font-mono">{{ rupiah($actionRequired['cash_deficits_amount'] ?? 0) }}</span>
                        </div>
                    </a>
                    @endif

                    @if(($actionRequired['completed_unpaid'] ?? 0) > 0)
                    <a href="{{ route('admin.orders.index', ['filter' => 'ready_to_bill']) }}" class="p-3 rounded-xl bg-rose-50/90 hover:bg-rose-100 transition-colors block text-xs border border-rose-200/60">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-rose-900 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                {{ $actionRequired['completed_unpaid'] }} pesanan tuntas belum lunas!
                            </span>
                            <span class="font-bold text-rose-700 font-mono">{{ rupiah($actionRequired['completed_receivable'] ?? 0) }}</span>
                        </div>
                    </a>
                    @endif

                    @if($actionRequired['new_inquiries'] > 0)
                    <a href="{{ route('admin.inquiries.index', ['status' => 'new']) }}" class="p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span class="font-medium text-slate-700">{{ $actionRequired['new_inquiries'] }} calon klien baru</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-400 text-[9px]"></i>
                    </a>
                    @endif

                    @if(array_sum(array_filter($actionRequired, fn ($k) => !in_array($k, ['cash_deficits_amount', 'completed_receivable']), ARRAY_FILTER_USE_KEY)) === 0)
                    <div class="p-3 rounded-xl bg-slate-50 text-slate-500 text-xs flex items-center gap-2">
                        <i class="fa-solid fa-check text-emerald-500"></i>
                        <span>Semua beres — tidak ada tindakan mendesak.</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Ringkasan Pesanan Aktif -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">Ringkasan Aktivitas</h4>
                    <span class="text-[10px] text-slate-400">Total Transaksi</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Total Pesanan</span>
                        <span class="text-base font-bold text-slate-900">{{ $stats['total_orders'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Pesanan Aktif</span>
                        <span class="text-base font-bold text-blue-600">{{ $stats['active_orders'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Pesanan Selesai</span>
                        <span class="text-base font-bold text-emerald-600">{{ $stats['completed_orders'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Pertanyaan Baru</span>
                        <span class="text-base font-bold text-purple-600">{{ $stats['new_inquiries'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Pesanan Terbaru (8 Kolom) -->
        <div class="lg:col-span-8 bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden">
            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Pesanan Terbaru</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Daftar transaksi dan status pembayaran terkini</p>
                </div>
                <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-2xs">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span>Buat Pesanan</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead>
                        <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                            <th class="py-2.5 px-4 font-normal">NO. ORDER</th>
                            <th class="py-2.5 px-4 font-normal">PELANGGAN</th>
                            <th class="py-2.5 px-4 font-normal text-right">TOTAL</th>
                            <th class="py-2.5 px-4 font-normal text-center">PEMBAYARAN</th>
                            <th class="py-2.5 px-4 font-normal">TAHAP</th>
                            <th class="py-2.5 px-4 font-normal text-center">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-brand-700">
                                <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">{{ $order->order_number }}</a>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $order->customer?->name ?? 'Pelanggan' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $order->name }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 text-right">{{ rupiah($order->grand_total) }}</td>
                            <td class="py-3 px-4 text-center">
                                @if($order->payment_status === 'paid')
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px]">Lunas</span>
                                @elseif($order->amount_paid > 0)
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px]">DP Masuk</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium text-[10px]">Belum Dibayar</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">{{ $order->current_stage_name }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $order->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $order->status === 'completed' ? 'Selesai' : ($order->status === 'cancelled' ? 'Dibatalkan' : 'Aktif') }}
                                </span>
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

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Grafik Batang Tren Arus Kas Bulanan (Kas Masuk vs Kas Keluar)
        const cashCtx = document.getElementById('cashflowBarChart');
        if (cashCtx) {
            new Chart(cashCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($monthlyTrend->pluck('label')) !!},
                    datasets: [
                        {
                            label: 'Kas Masuk',
                            data: {!! json_encode($monthlyTrend->pluck('kas_masuk')) !!},
                            backgroundColor: '#059669', // Emerald green
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Kas Keluar',
                            data: {!! json_encode($monthlyTrend->pluck('kas_keluar')) !!},
                            backgroundColor: '#e11d48', // Rose red
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': Rp ' + Number(context.raw || 0).toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) {
                                    if (val >= 1000000) {
                                        return 'Rp ' + (val / 1000000).toLocaleString('id-ID') + ' jt';
                                    }
                                    return 'Rp ' + val.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Grafik Garis Tren Laba Bersih & Estimasi Laba
        const profitCtx = document.getElementById('profitLineChart');
        if (profitCtx) {
            new Chart(profitCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($monthlyTrend->pluck('label')) !!},
                    datasets: [
                        {
                            label: 'Estimasi Laba Kotor',
                            data: {!! json_encode($monthlyTrend->pluck('estimasi_laba')) !!},
                            borderColor: '#2563eb', // Blue
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3
                        },
                        {
                            label: 'Laba Bersih',
                            data: {!! json_encode($monthlyTrend->pluck('laba_bersih')) !!},
                            borderColor: '#059669', // Emerald
                            backgroundColor: 'rgba(5, 150, 105, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': Rp ' + Number(context.raw || 0).toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) {
                                    if (Math.abs(val) >= 1000000) {
                                        return 'Rp ' + (val / 1000000).toLocaleString('id-ID') + ' jt';
                                    }
                                    return 'Rp ' + val.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
