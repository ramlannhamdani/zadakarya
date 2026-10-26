@extends('layouts.admin')

@section('title', 'Neraca & Laba Rugi Konveksi')

@section('header-actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.assets.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-all">
        <i class="fa-solid fa-boxes-stacked text-[11px]"></i>
        <span>Kelola Aset</span>
    </a>
    <a href="{{ route('admin.spreadsheet.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs font-semibold transition-all">
        <i class="fa-solid fa-file-excel text-[11px]"></i>
        <span>Buka Spreadsheet</span>
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{ currentTab: '{{ $activeTab }}' }">

    <!-- 1. KPI HIGHLIGHTS: VALUASI & POSISI KEUANGAN -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Valuasi Bersih (Net Worth) -->
        <div class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-950 text-white rounded-2xl p-4 sm:p-5 shadow-md relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-brand-200 uppercase tracking-wider">Valuasi Bersih Konveksi</p>
                    <span class="w-6 h-6 rounded-lg bg-white/10 flex items-center justify-center text-brand-200 text-xs">
                        <i class="fa-solid fa-gem"></i>
                    </span>
                </div>
                <p class="text-xl sm:text-2xl font-bold tracking-tight">Rp {{ number_format($balanceSheet['net_worth'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-brand-200/80 mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-shield-halved text-[10px]"></i> Total Aset - Total Utang
                </p>
            </div>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-white/5 rounded-full blur-xl pointer-events-none"></div>
        </div>

        <!-- Total Aset / Aktiva -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Aset (Aktiva)</p>
                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-vault"></i>
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Rp {{ number_format($balanceSheet['total_assets'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Lancar: Rp {{ number_format($balanceSheet['current_assets']['total'], 0, ',', '.') }} | Tetap: Rp {{ number_format($balanceSheet['fixed_assets']['total'], 0, ',', '.') }}</p>
        </div>

        <!-- Total Kewajiban / Utang -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Kewajiban (Utang)</p>
                <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Rp {{ number_format($balanceSheet['liabilities']['total'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Sisa pinjaman / talangan aktif</p>
        </div>

        <!-- Status Neraca / Keseimbangan -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Keseimbangan Neraca</p>
                    <span class="w-6 h-6 rounded-lg {{ $balanceSheet['is_balanced'] ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center text-xs">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    @if($balanceSheet['is_balanced'])
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                            <i class="fa-solid fa-circle-check text-[10px]"></i> Seimbang (Balanced)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">
                            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Selisih Rp {{ number_format(abs($balanceSheet['total_assets'] - $balanceSheet['total_liabilities_and_equity']), 0, ',', '.') }}
                        </span>
                    @endif
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">Aktiva = Pasiva (Kewajiban + Ekuitas)</p>
        </div>
    </section>

    <!-- 2. TAB NAVIGATOR -->
    <div class="flex items-center justify-between border-b border-slate-200">
        <div class="flex items-center gap-2 sm:gap-4">
            <button type="button" @click="currentTab = 'neraca'"
                    :class="currentTab === 'neraca' ? 'border-brand-600 text-brand-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                    class="py-3 px-3 sm:px-4 border-b-2 text-xs sm:text-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-scale-balanced text-sm"></i>
                <span>Neraca Skontro (Kiri & Kanan)</span>
            </button>
            <button type="button" @click="currentTab = 'laba_rugi'"
                    :class="currentTab === 'laba_rugi' ? 'border-brand-600 text-brand-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                    class="py-3 px-3 sm:px-4 border-b-2 text-xs sm:text-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-sm"></i>
                <span>Laporan Laba Rugi</span>
            </button>
        </div>
        <div class="hidden sm:block text-xs text-slate-400">
            Standar Akuntansi Konveksi (SAK EMKM)
        </div>
    </div>

    <!-- 3. TAB 1: NERACA BENTUK SKONTRO (KIRI AKTIVA & KANAN PASIVA) -->
    <div x-show="currentTab === 'neraca'" x-transition class="space-y-6">

        <!-- Info Card: Format Neraca Skontro -->
        <div class="bg-amber-50/60 border border-amber-200/70 rounded-2xl p-4 text-xs text-amber-900 flex items-start gap-3">
            <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5 shrink-0"></i>
            <div>
                <p class="font-bold">Penyajian Neraca Bentuk Skontro (T-Account Kiri & Kanan):</p>
                <p class="text-amber-800 mt-0.5">
                    Sisi Kiri menyajikan <strong>Aktiva / Aset</strong> (Aset Lancar & Aset Tetap Mesin Konveksi). Sisi Kanan menyajikan <strong>Pasiva</strong> (Kewajiban Utang & Ekuitas Modal). Valuasi bersih konveksi diperoleh dari total seluruh aset dikurangi total kewajiban.
                </p>
            </div>
        </div>

        <!-- THE SKONTRO TWO-COLUMN GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <!-- ================= SISI KIRI: AKTIVA / ASET ================= -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden flex flex-col h-full">
                <!-- Header Kolom Kiri -->
                <div class="bg-slate-50 px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">1</span>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">AKTIVA (ASET)</h3>
                    </div>
                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200/60">
                        Total: Rp {{ number_format($balanceSheet['total_assets'], 0, ',', '.') }}
                    </span>
                </div>

                <div class="p-5 space-y-6 flex-1">
                    <!-- A. ASET LANCAR -->
                    <div>
                        <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-money-bill-transfer text-emerald-600 text-xs"></i>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">A. ASET LANCAR</h4>
                            </div>
                            <span class="text-xs font-semibold text-slate-600">Rp {{ number_format($balanceSheet['current_assets']['total'], 0, ',', '.') }}</span>
                        </div>

                        <ul class="space-y-2.5 text-xs">
                            <li class="flex items-center justify-between py-1.5 px-3 rounded-xl bg-slate-50/70 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <div>
                                        <p class="font-medium text-slate-800">Kas & Setara Kas (Saldo Bank)</p>
                                        <p class="text-[10px] text-slate-400">Total penerimaan dikurangi total pengeluaran riil</p>
                                    </div>
                                </div>
                                <span class="font-semibold text-slate-900">Rp {{ number_format($balanceSheet['current_assets']['cash'], 0, ',', '.') }}</span>
                            </li>
                            <li class="flex items-center justify-between py-1.5 px-3 rounded-xl bg-slate-50/70 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    <div>
                                        <p class="font-medium text-slate-800">Piutang Usaha Customer</p>
                                        <p class="text-[10px] text-slate-400">Sisa tagihan invoice order yang belum dilunasi</p>
                                    </div>
                                </div>
                                <span class="font-semibold text-slate-900">Rp {{ number_format($balanceSheet['current_assets']['receivables'], 0, ',', '.') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- B. ASET TETAP KONVEKSI -->
                    <div>
                        <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-boxes-stacked text-brand-600 text-xs"></i>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">B. ASET TETAP & VALUASI MESIN</h4>
                            </div>
                            <span class="text-xs font-semibold text-slate-600">Rp {{ number_format($balanceSheet['fixed_assets']['total'], 0, ',', '.') }}</span>
                        </div>

                        <ul class="space-y-2 text-xs">
                            @foreach($balanceSheet['fixed_assets']['items'] as $catKey => $catData)
                            <li class="flex items-center justify-between py-1.5 px-3 rounded-xl hover:bg-slate-50 transition-colors border border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-[11px]">
                                        <i class="fa-solid {{ $catData['icon'] }}"></i>
                                    </span>
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $catData['name'] }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $catData['count'] }} item terdaftar</p>
                                    </div>
                                </div>
                                <span class="font-semibold text-slate-900">Rp {{ number_format($catData['value'], 0, ',', '.') }}</span>
                            </li>
                            @endforeach
                        </ul>

                        <!-- Ringkasan Nilai Buku & Depresiasi -->
                        <div class="mt-3 p-3 bg-slate-50/80 rounded-xl text-[11px] text-slate-500 space-y-1">
                            <div class="flex justify-between">
                                <span>Nilai Beli Historis (Cost):</span>
                                <span class="font-medium text-slate-700">Rp {{ number_format($balanceSheet['fixed_assets']['historical_cost'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Akumulasi Depresiasi / Penurunan Nilai:</span>
                                <span class="font-medium text-rose-600">- Rp {{ number_format($balanceSheet['fixed_assets']['depreciation'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between font-semibold text-slate-800 pt-1 border-t border-slate-200">
                                <span>Valuasi Pasar Aset Tetap Saat Ini:</span>
                                <span class="text-brand-700">Rp {{ number_format($balanceSheet['fixed_assets']['total'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Total Aktiva -->
                <div class="bg-emerald-50/80 px-5 py-4 border-t border-emerald-200 flex items-center justify-between mt-auto">
                    <span class="text-sm font-bold text-emerald-950 uppercase tracking-wide">TOTAL AKTIVA (ASET)</span>
                    <span class="text-base font-extrabold text-emerald-700">Rp {{ number_format($balanceSheet['total_assets'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- ================= SISI KANAN: PASIVA / KEWAJIBAN & EKUITAS ================= -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden flex flex-col h-full">
                <!-- Header Kolom Kanan -->
                <div class="bg-slate-50 px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-brand-700 text-white flex items-center justify-center text-xs font-bold">2</span>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">PASIVA (KEWAJIBAN & EKUITAS)</h3>
                    </div>
                    <span class="text-xs font-semibold text-brand-700 bg-brand-50 px-2.5 py-0.5 rounded-full border border-brand-200/60">
                        Total: Rp {{ number_format($balanceSheet['total_liabilities_and_equity'], 0, ',', '.') }}
                    </span>
                </div>

                <div class="p-5 space-y-6 flex-1">
                    <!-- A. KEWAJIBAN / UTANG -->
                    <div>
                        <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-file-invoice-dollar text-amber-600 text-xs"></i>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">A. KEWAJIBAN (LIABILITAS)</h4>
                            </div>
                            <span class="text-xs font-semibold text-slate-600">Rp {{ number_format($balanceSheet['liabilities']['total'], 0, ',', '.') }}</span>
                        </div>

                        <ul class="space-y-2.5 text-xs">
                            <li class="flex items-center justify-between py-1.5 px-3 rounded-xl bg-slate-50/70 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <div>
                                        <p class="font-medium text-slate-800">Utang Pinjaman & Dana Talangan</p>
                                        <p class="text-[10px] text-slate-400">Sisa pokok pinjaman yang belum diselesaikan</p>
                                    </div>
                                </div>
                                <span class="font-semibold text-slate-900">Rp {{ number_format($balanceSheet['liabilities']['loans'], 0, ',', '.') }}</span>
                            </li>
                            <li class="flex items-center justify-between py-1.5 px-3 rounded-xl bg-slate-50/70 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                    <div>
                                        <p class="font-medium text-slate-500">Utang Usaha / Vendor Bahan</p>
                                        <p class="text-[10px] text-slate-400">Kewajiban invoice tempo kain & aksesoris</p>
                                    </div>
                                </div>
                                <span class="font-semibold text-slate-500">Rp 0 (Lunas Cash)</span>
                            </li>
                        </ul>
                    </div>

                    <!-- B. EKUITAS / MODAL BERSIH -->
                    <div>
                        <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-scale-unbalanced-flip text-brand-600 text-xs"></i>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">B. EKUITAS (MODAL BERSIH)</h4>
                            </div>
                            <span class="text-xs font-semibold text-slate-600">Rp {{ number_format($balanceSheet['equity']['total'], 0, ',', '.') }}</span>
                        </div>

                        <ul class="space-y-2.5 text-xs">
                            <li class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50/70 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                                    <div>
                                        <p class="font-medium text-slate-800">Modal Disetor Pemilik</p>
                                        <p class="text-[10px] text-slate-400">Penyertaan awal aset konveksi & kas usaha</p>
                                    </div>
                                </div>
                                <span class="font-semibold text-slate-900">Rp {{ number_format($balanceSheet['equity']['initial_capital'], 0, ',', '.') }}</span>
                            </li>
                            <li class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50/70 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <div>
                                        <p class="font-medium text-slate-800">Akumulasi Laba Ditahan</p>
                                        <p class="text-[10px] text-slate-400">Total laba bersih operasional dikurangi prive pemilik</p>
                                    </div>
                                </div>
                                <span class="font-semibold {{ $balanceSheet['equity']['retained_earnings'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                    Rp {{ number_format($balanceSheet['equity']['retained_earnings'], 0, ',', '.') }}
                                </span>
                            </li>
                        </ul>

                        <!-- Valuasi Bersih Info -->
                        <div class="mt-4 p-3 bg-brand-50/60 rounded-xl border border-brand-100 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-brand-900 font-semibold">Valuasi Bersih Usaha (Net Worth):</span>
                                <span class="text-sm font-bold text-brand-800">Rp {{ number_format($balanceSheet['net_worth'], 0, ',', '.') }}</span>
                            </div>
                            <p class="text-[10px] text-brand-700/80 mt-1">
                                Nilai riil konveksi bila dilikuidasi hari ini setelah melunasi seluruh kewajiban.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer Total Pasiva -->
                <div class="bg-brand-50/80 px-5 py-4 border-t border-brand-200 flex items-center justify-between mt-auto">
                    <span class="text-sm font-bold text-brand-950 uppercase tracking-wide">TOTAL PASIVA (KEWAJIBAN & EKUITAS)</span>
                    <span class="text-base font-extrabold text-brand-700">Rp {{ number_format($balanceSheet['total_liabilities_and_equity'], 0, ',', '.') }}</span>
                </div>
            </div>

        </div>
    </div>

    <!-- 4. TAB 2: LAPORAN LABA RUGI -->
    <div x-show="currentTab === 'laba_rugi'" x-transition class="space-y-6">

        <!-- Laba Rugi Report Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 sm:p-7 max-w-4xl mx-auto">
            <div class="text-center pb-6 border-b border-slate-100">
                <span class="text-xs font-semibold text-brand-700 bg-brand-50 px-3 py-1 rounded-full uppercase tracking-wider">Laporan Keuangan Komprehensif</span>
                <h3 class="text-lg sm:text-xl font-bold text-slate-900 mt-2">Laporan Laba Rugi Operasional</h3>
                <p class="text-xs text-slate-500 mt-1">Zada Karya Production — Periode Berjalan</p>
            </div>

            <div class="mt-6 space-y-5 text-xs sm:text-sm">

                <!-- 1. PENDAPATAN -->
                <div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 font-bold text-slate-900 uppercase">
                        <span>1. Pendapatan Penjualan (Order)</span>
                        <span class="text-emerald-700">Rp {{ number_format($incomeStatement['sales'], 0, ',', '.') }}</span>
                    </div>
                    <div class="pl-4 py-1.5 flex justify-between text-slate-500 text-xs">
                        <span>• Penjualan Tunai / Pembayaran Masuk</span>
                        <span>Rp {{ number_format($incomeStatement['paid_sales'], 0, ',', '.') }}</span>
                    </div>
                    <div class="pl-4 py-1.5 flex justify-between text-slate-500 text-xs">
                        <span>• Piutang Berjalan (Belum Lunas)</span>
                        <span>Rp {{ number_format($incomeStatement['receivables'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- 2. HPP -->
                <div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 font-bold text-slate-900 uppercase">
                        <span>2. Harga Pokok Penjualan (HPP Produksi)</span>
                        <span class="text-rose-600">- Rp {{ number_format($incomeStatement['hpp'], 0, ',', '.') }}</span>
                    </div>
                    <div class="pl-4 py-1.5 flex justify-between text-slate-500 text-xs">
                        <span>• Bahan Baku Kain, Rib & Kerah</span>
                        <span class="italic text-slate-400">Tercatat di Rincian Biaya</span>
                    </div>
                    <div class="pl-4 py-1.5 flex justify-between text-slate-500 text-xs">
                        <span>• Jasa Sablon, DTF, Polyflex & Bordir Komputer</span>
                        <span class="italic text-slate-400">Tercatat di Rincian Biaya</span>
                    </div>
                    <div class="pl-4 py-1.5 flex justify-between text-slate-500 text-xs">
                        <span>• Biaya CMT Jahit & Aksesoris (Kancing, Resleting, Label)</span>
                        <span class="italic text-slate-400">Tercatat di Rincian Biaya</span>
                    </div>
                </div>

                <!-- 3. LABA KOTOR -->
                <div class="p-3.5 bg-emerald-50/70 border border-emerald-200/60 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="font-bold text-emerald-950 uppercase">LABA KOTOR (GROSS PROFIT)</span>
                        <p class="text-[11px] text-emerald-700">Margin Laba Kotor: {{ $incomeStatement['gross_margin'] }}%</p>
                    </div>
                    <span class="text-base font-extrabold text-emerald-700">Rp {{ number_format($incomeStatement['gross_profit'], 0, ',', '.') }}</span>
                </div>

                <!-- 4. BEBAN OPERASIONAL -->
                <div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 font-bold text-slate-900 uppercase">
                        <span>3. Beban Operasional Konveksi</span>
                        <span class="text-rose-600">- Rp {{ number_format($incomeStatement['opex_convection'], 0, ',', '.') }}</span>
                    </div>
                    <div class="pl-4 py-1.5 flex justify-between text-slate-500 text-xs">
                        <span>• Listrik Workshop, Maintenance Mesin, Sparepart, Packing</span>
                        <span class="italic text-slate-400">Tercatat di Beban Konveksi</span>
                    </div>
                </div>

                <!-- 5. LABA OPERASIONAL (EBIT) -->
                <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between">
                    <span class="font-bold text-slate-900 uppercase">LABA OPERASIONAL KONVEKSI (EBIT)</span>
                    <span class="text-sm font-bold text-slate-900">Rp {{ number_format($incomeStatement['operating_profit'], 0, ',', '.') }}</span>
                </div>

                <!-- 6. PRIVE PEMILIK -->
                <div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 font-bold text-slate-900 uppercase">
                        <span>4. Pengeluaran Pribadi / Prive Pemilik</span>
                        <span class="text-rose-600">- Rp {{ number_format($incomeStatement['prive'], 0, ',', '.') }}</span>
                    </div>
                    <div class="pl-4 py-1.5 flex justify-between text-slate-500 text-xs">
                        <span>• Penarikan dana untuk keperluan pribadi di luar konveksi</span>
                        <span class="italic text-slate-400">Tercatat di Beban Pribadi</span>
                    </div>
                </div>

                <!-- 7. LABA BERSIH DITAHAN -->
                <div class="p-4 bg-gradient-to-r from-brand-900 to-brand-800 text-white rounded-xl flex items-center justify-between shadow-sm">
                    <div>
                        <span class="font-bold uppercase tracking-wide">LABA BERSIH AKHIR (LABA DITAHAN)</span>
                        <p class="text-[11px] text-brand-200">Menambah nilai ekuitas pada Neraca</p>
                    </div>
                    <span class="text-lg sm:text-xl font-extrabold tracking-tight">Rp {{ number_format($incomeStatement['net_profit'], 0, ',', '.') }}</span>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection
