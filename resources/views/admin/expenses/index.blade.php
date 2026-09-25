@extends('layouts.admin')

@section('title', 'Beban Operasional')

@section('content')
{{-- Summary cards --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="admin-card border-l-4 !border-l-red-500">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Beban Bulan Ini</p>
        <p class="mt-2 text-2xl font-extrabold text-red-600">{{ rupiah($thisMonthTotal) }}</p>
        <p class="mt-1 text-xs text-neutral-500">{{ now()->translatedFormat('F Y') }}</p>
    </div>
    <div class="admin-card border-l-4 !border-l-amber-500">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Beban Terbesar Bulan Ini</p>
        <p class="mt-2 text-lg font-extrabold text-ink truncate" title="{{ $topCategory ? $topCategory['name'] : '—' }}">
            {{ $topCategory ? $topCategory['name'] : '—' }}
        </p>
        <p class="mt-1 text-xs text-neutral-500">{{ $topCategory ? rupiah($topCategory['amount']) : 'Belum ada data' }}</p>
    </div>
    <div class="admin-card border-l-4 !border-l-brand-600">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Beban Tahun Ini</p>
        <p class="mt-2 text-2xl font-extrabold text-brand-600">{{ rupiah($thisYearTotal) }}</p>
        <p class="mt-1 text-xs text-neutral-500">Tahun {{ now()->format('Y') }}</p>
    </div>
    <div class="admin-card border-l-4 !border-l-slate-600">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Seluruh Beban</p>
        <p class="mt-2 text-2xl font-extrabold text-slate-700">{{ rupiah($allTimeTotal) }}</p>
        <p class="mt-1 text-xs text-neutral-500">Akumulasi pengeluaran operasional</p>
    </div>
</div>

{{-- Filter and Action bar --}}
<div class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari keterangan / no. bukti..." class="form-input min-w-0 !py-2 text-sm sm:!w-64">

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
        @if(request()->anyFilled(['q', 'category']) || request('period') === 'all')
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
    <table class="w-full min-w-[850px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">No. Ref</th>
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
                    <td colspan="8" class="px-5 py-12 text-center text-neutral-500">
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
