@extends('layouts.admin')

@section('title', 'Beban Operasional')

@section('content')
{{-- Financial Position: 5 Core Metrics --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="admin-card border-l-4 !border-l-green-500">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Pemasukan (Kas Masuk)</p>
        <p class="mt-2 text-2xl font-extrabold text-green-600">{{ rupiah($periodIncome) }}</p>
        <p class="mt-1 text-xs text-neutral-500">Pembayaran masuk {{ $isAllPeriod ? 'seluruhnya' : $periodLabel }}</p>
    </div>

    <div class="admin-card border-l-4 !border-l-blue-500">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Biaya HPP (Produksi)</p>
        <p class="mt-2 text-2xl font-extrabold text-blue-600">{{ rupiah($periodHpp) }}</p>
        <p class="mt-1 text-xs text-neutral-500">Kain, trims, makloon &amp; CMT</p>
    </div>

    <div class="admin-card border-l-4 !border-l-indigo-600 bg-indigo-50/10">
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Ops. Konveksi</p>
            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">Bisnis</span>
        </div>
        <p class="mt-2 text-2xl font-extrabold text-indigo-700">{{ rupiah($periodConvectionExpense) }}</p>
        <p class="mt-1 text-xs text-neutral-500">Total semua: <strong class="text-ink font-semibold">{{ rupiah($totalAllConvectionExpense) }}</strong></p>
    </div>

    <div class="admin-card border-l-4 !border-l-rose-500 bg-rose-50/10">
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Ops. Pribadi</p>
            <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded">Prive Owner</span>
        </div>
        <p class="mt-2 text-2xl font-extrabold text-rose-600">{{ rupiah($periodPersonalExpense) }}</p>
        <p class="mt-1 text-xs text-neutral-500">Total semua: <strong class="text-ink font-semibold">{{ rupiah($totalAllPersonalExpense) }}</strong></p>
    </div>

    <div class="admin-card border-l-4 {{ $totalCurrentBalance >= 0 ? '!border-l-emerald-500 bg-emerald-50/20' : '!border-l-red-500 bg-red-50/20' }}">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Saldo Kas Riil Saat Ini</p>
        <p class="mt-2 text-2xl font-extrabold {{ $totalCurrentBalance >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
            {{ rupiah($totalCurrentBalance) }}
        </p>
        <p class="mt-1 text-xs text-neutral-500">Total Kas Masuk − Seluruh Pengeluaran</p>
    </div>
</div>

{{-- Comprehensive Financial Statement Panels --}}
<div class="mt-4 grid gap-4 lg:grid-cols-2">
    {{-- Panel 1: Arus Kas & Posisi Saldo Riil --}}
    <div class="admin-card">
        <div class="flex items-center justify-between pb-2.5 border-b border-line">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                <span class="text-xs font-extrabold uppercase tracking-wider text-neutral-700">Laporan Arus Kas ({{ $isAllPeriod ? 'Semua Waktu' : $periodLabel }})</span>
            </div>
            <span class="text-xs font-medium text-neutral-500">
                Sisa Kas Periode Ini: <strong class="font-bold {{ $periodNetBalance >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ rupiah($periodNetBalance) }}</strong>
            </span>
        </div>
        <div class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-neutral-600">Total Kas Masuk (Pembayaran Customer)</span>
                <span class="font-bold text-green-600">{{ rupiah($periodIncome) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-neutral-600">Total Pengeluaran Kas (HPP + Semua Beban)</span>
                <span class="font-bold text-red-600">- {{ rupiah($periodTotalOutflow) }}</span>
            </div>
            <div class="flex flex-col gap-1 text-xs text-neutral-500 pl-3">
                <div class="flex justify-between">
                    <span>↳ Biaya Produksi (HPP Pesanan):</span>
                    <span class="font-medium text-neutral-700">{{ rupiah($periodHpp) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>↳ Ops. Konveksi (Workshop & Bisnis):</span>
                    <span class="font-medium text-indigo-700">{{ rupiah($periodConvectionExpense) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>↳ Ops. Pribadi (Prive / Keluarga):</span>
                    <span class="font-medium text-rose-700">{{ rupiah($periodPersonalExpense) }}</span>
                </div>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-line text-sm">
                <span class="font-extrabold text-ink">Saldo Kas Berjalan Kumulatif</span>
                <span class="font-extrabold text-base {{ $totalCurrentBalance >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                    {{ rupiah($totalCurrentBalance) }}
                </span>
            </div>
            @if(isset($activeAdvances) && $activeAdvances > 0)
                <div class="mt-2.5 rounded-lg border border-amber-200 bg-amber-50/80 p-2.5 text-xs text-amber-900 flex flex-wrap items-center justify-between gap-2">
                    <span class="flex items-center gap-1.5">
                        <span>⚠️</span>
                        <span>Kas Sedang Menalangi HPP Pesanan (DP Kurang): <strong>{{ rupiah($activeAdvances) }}</strong> ({{ $activeDeficitCount }} pesanan)</span>
                    </span>
                    <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}" class="font-bold text-amber-900 hover:underline">Lihat Pesanan &rarr;</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Panel 2: Kinerja Laba Bisnis Konveksi --}}
    <div class="admin-card">
        <div class="flex items-center justify-between pb-2.5 border-b border-line">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full bg-brand-600"></span>
                <span class="text-xs font-extrabold uppercase tracking-wider text-neutral-700">Laba Bisnis Konveksi ({{ $isAllPeriod ? 'Semua Waktu' : $periodLabel }})</span>
            </div>
            @if($topCategory)
                <span class="text-xs text-neutral-500 truncate" title="{{ $topCategory['name'] }}: {{ rupiah($topCategory['amount']) }}">
                    Beban Terbesar: <strong class="text-ink">{{ $topCategory['name'] }}</strong>
                </span>
            @endif
        </div>
        <div class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-neutral-600">Total Nilai Pesanan (Omset Periode)</span>
                <span class="font-bold text-ink">{{ rupiah($periodOrderValue) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-neutral-600">Laba Kotor Pesanan (Omset − HPP)</span>
                <span class="font-bold text-indigo-600">{{ rupiah($periodGrossProfit) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-neutral-600">Beban Ops. Konveksi (Pengurang Laba Bisnis)</span>
                <span class="font-bold text-rose-600">- {{ rupiah($periodConvectionExpense) }}</span>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-line text-sm">
                <span class="font-extrabold text-ink">Laba Bersih Konveksi</span>
                <span class="font-extrabold text-base {{ $periodNetProfit >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                    {{ rupiah($periodNetProfit) }}
                </span>
            </div>
            <div class="mt-2 rounded bg-neutral-50 px-3 py-2 text-xs text-neutral-600">
                💡 <strong>Catatan Prive:</strong> Ops. Pribadi ({{ rupiah($periodPersonalExpense) }}) tidak mengurangi laba operasional konveksi, tetapi dicatat sebagai penarikan kas pemilik.
            </div>
        </div>
    </div>
</div>

{{-- Filter and Action bar --}}
<div class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari keterangan / no. ref..." class="form-input min-w-0 !py-2 text-sm sm:!w-60">

        <select name="expense_type" class="form-input !py-2 text-sm !w-auto">
            <option value="">— Semua Jenis —</option>
            @foreach(\App\Models\OperationalExpense::TYPES as $key => $label)
                <option value="{{ $key }}" @selected(request('expense_type') === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="category" class="form-input !py-2 text-sm !w-auto">
            <option value="">— Semua Kategori —</option>
            @foreach(\App\Models\OperationalExpense::CATEGORIES as $key => $label)
                <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="month" class="form-input !py-2 text-sm !w-auto">
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
            @endforeach
        </select>

        <select name="year" class="form-input !py-2 text-sm !w-auto">
            @foreach($years as $y)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>

        @if(request('period') === 'all')
            <input type="hidden" name="period" value="all">
        @endif

        <button type="submit" class="btn-outline !px-3 !py-2 text-sm">Filter</button>
        @if(request()->anyFilled(['q', 'expense_type', 'category']) || request('period') === 'all')
            <a href="{{ route('admin.expenses.index') }}" class="text-xs text-neutral-500 hover:text-red-600">Reset</a>
        @endif
    </form>

    <div class="flex items-center gap-2">
        @if(request('period') === 'all')
            <a href="{{ route('admin.expenses.index') }}" class="btn-outline !px-3 !py-2 text-sm">Lihat Per Bulan</a>
        @else
            <a href="{{ route('admin.expenses.index', ['period' => 'all'] + request()->except('period')) }}" class="btn-outline !px-3 !py-2 text-sm">Lihat Semua Periode</a>
        @endif

        <a href="{{ route('admin.expenses.create') }}" class="btn-primary !px-4 !py-2 text-sm inline-flex items-center gap-1.5">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            <span>Catat Beban Baru</span>
        </a>
    </div>
</div>

{{-- Data Table --}}
<div class="admin-card mt-5 overflow-x-auto !p-0">
    <table class="w-full min-w-[900px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">No. Ref</th>
                <th class="px-5 py-3">Jenis</th>
                <th class="px-5 py-3">Kategori</th>
                <th class="px-5 py-3">Keterangan</th>
                <th class="px-5 py-3 text-right">Nominal</th>
                <th class="px-5 py-3">Metode</th>
                <th class="px-5 py-3 text-center">Bukti</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($expenses as $expense)
                <tr class="hover:bg-cream/40 transition">
                    <td class="whitespace-nowrap px-5 py-3.5 font-medium text-neutral-700">
                        {{ $expense->expense_date->format('d/m/Y') }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-semibold text-neutral-600">
                        {{ $expense->reference_no }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $expense->type_color }}">
                            {{ $expense->type_label }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $expense->category_color }}">
                            {{ $expense->category_label }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="font-bold text-ink">{{ $expense->title }}</p>
                        @if($expense->notes)
                            <p class="text-xs text-neutral-500 mt-0.5">{{ $expense->notes }}</p>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right font-extrabold text-red-600">
                        {{ rupiah($expense->amount) }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-neutral-600">
                        {{ $expense->method_label }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-center">
                        @if($expense->receipt_path)
                            <a href="{{ route('admin.expenses.receipt', $expense) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline" title="Lihat Bukti Struk">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                <span>Lihat</span>
                            </a>
                        @else
                            <span class="text-xs text-neutral-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right text-xs">
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ route('admin.expenses.edit', $expense) }}" class="font-semibold text-brand-600 hover:text-brand-800">Edit</a>
                            <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" onsubmit="return confirm('Yakin ingin menghapus beban operasional {{ $expense->reference_no }} ({{ $expense->title }})?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-red-600 hover:text-red-800">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-5 py-12 text-center text-neutral-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-10 w-10 text-neutral-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="font-medium">Belum ada beban operasional tercatat pada periode ini.</p>
                            <a href="{{ route('admin.expenses.create') }}" class="btn-primary !px-3.5 !py-1.5 text-xs mt-3">Catat Beban Baru</a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">{{ $expenses->links() }}</div>
@endsection
