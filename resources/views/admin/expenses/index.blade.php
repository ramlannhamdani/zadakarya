@extends('layouts.admin')

@section('title', 'Beban Operasional & Pengeluaran Kas')

@section('header-actions')
<a href="{{ route('admin.expenses.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Catat Pengeluaran</span>
</a>
@endsection

@section('content')
<!-- 1. SUMMARY METRICS TILES DINAMIS (5 CARDS) -->
<section class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 lg:gap-6">
        <!-- 1. Uang Masuk -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Uang Masuk</p>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-emerald-600">{{ rupiah($periodIncome) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Kas masuk diterima</p>
        </div>

        <!-- 2. HPP (Belanja Pesanan) -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">HPP (Bahan)</p>
                @php
                    $totalOut = $periodTotalOutflow > 0 ? $periodTotalOutflow : 1;
                    $hppPct = round(($periodHpp / $totalOut) * 100, 1);
                    $convectionPct = round(($periodConvectionExpense / $totalOut) * 100, 1);
                    $personalPct = round(($periodPersonalExpense / $totalOut) * 100, 1);
                @endphp
                <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">{{ $hppPct }}%</span>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-rose-600">{{ rupiah($periodHpp) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Kain & makloon</p>
        </div>

        <!-- 3. Ops Konveksi -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Ops. Konveksi</p>
                <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">{{ $convectionPct }}%</span>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-rose-600">{{ rupiah($periodConvectionExpense) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Overhead workshop</p>
        </div>

        <!-- 4. Ops Pribadi (Prive) -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Ops. Pribadi</p>
                <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">{{ $personalPct }}%</span>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-rose-600">{{ rupiah($periodPersonalExpense) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Prive owner</p>
        </div>

        <!-- 5. Saldo Kas Riil -->
        <div class="bg-slate-50/70 rounded-xl p-3.5 sm:p-4 col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Saldo Kas Riil</p>
                <span class="text-[10px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full">Kas Bersih</span>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold {{ $totalCurrentBalance >= 0 ? 'text-slate-900' : 'text-rose-600' }}">
                    {{ rupiah($totalCurrentBalance) }}
                </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Saldo riil di tangan</p>
        </div>
    </div>
</section>

<!-- 2. FILTER & ACTION TOOLBAR -->
<section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Filter Tabs Berdasarkan Jenis Beban -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs no-scrollbar">
            <a href="{{ route('admin.expenses.index', array_merge(request()->except('expense_type'), ['expense_type' => ''])) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !request('expense_type') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Semua Beban
            </a>
            <a href="{{ route('admin.expenses.index', array_merge(request()->except('expense_type'), ['expense_type' => 'convection'])) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ request('expense_type') === 'convection' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Ops. Konveksi (Bisnis)
            </a>
            <a href="{{ route('admin.expenses.index', array_merge(request()->except('expense_type'), ['expense_type' => 'personal'])) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ request('expense_type') === 'personal' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Ops. Pribadi (Prive)
            </a>
        </div>

        <!-- Search & Filter Options -->
        <form method="GET" action="{{ route('admin.expenses.index') }}" class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
            @if(request('expense_type'))<input type="hidden" name="expense_type" value="{{ request('expense_type') }}">@endif

            <!-- Search Input -->
            <div class="relative flex-1 min-w-[150px] sm:w-56 sm:flex-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari Ref / Keterangan..."
                       class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
            </div>

            <!-- Bulan & Tahun Filter Dropdown -->
            <div class="flex items-center gap-1.5">
                <select name="month" class="bg-slate-50 text-xs text-slate-700 rounded-xl px-2.5 sm:px-3 py-2 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
                    @endforeach
                </select>

                <select name="year" class="bg-slate-50 text-xs text-slate-700 rounded-xl px-2.5 sm:px-3 py-2 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors shrink-0">
                Filter
            </button>
            @if(request()->anyFilled(['q', 'expense_type', 'category']) || request('period') === 'all')
                <a href="{{ route('admin.expenses.index') }}" class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors shrink-0">
                    Reset
                </a>
            @endif
        </form>

    </div>
</section>

<!-- 3. DATA TABLE BEBAN -->
<section class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
            <thead>
                <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">TANGGAL</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">NO. REF</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">JENIS</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">KATEGORI</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">KETERANGAN</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-right">NOMINAL</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">METODE</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">BUKTI</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($expenses as $expense)
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3.5 px-5 text-slate-500">
                        {{ $expense->expense_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-5 font-mono text-slate-600 font-medium">
                        {{ $expense->reference_no }}
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $expense->type_color }}">
                            {{ $expense->type_label }}
                        </span>
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700">
                            {{ $expense->category_label }}
                        </span>
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="font-bold text-slate-900 block">{{ $expense->title }}</span>
                        @if($expense->notes)
                            <span class="text-[11px] text-slate-400 block">{{ $expense->notes }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 font-bold text-rose-600 text-right">
                        {{ rupiah($expense->amount) }}
                    </td>
                    <td class="py-3.5 px-5 text-center text-slate-600">
                        {{ $expense->method_label }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        @if($expense->receipt_path)
                            <a href="{{ route('admin.expenses.receipt', $expense) }}" target="_blank" title="Lihat Bukti Struk"
                               class="p-1.5 rounded-lg text-brand-600 hover:bg-brand-50 inline-block transition-colors">
                                <i class="fa-solid fa-paperclip text-xs"></i>
                            </a>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.expenses.edit', $expense) }}" title="Edit Beban"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}"
                                  onsubmit="return confirm('Hapus beban {{ $expense->reference_no }} ({{ $expense->title }})?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Hapus Beban" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                        Belum ada beban operasional tercatat pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer Pagination -->
    @if($expenses->hasPages() || $expenses->total() > 0)
    <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
        <span>Menampilkan {{ $expenses->count() }} dari {{ $expenses->total() }} catatan pengeluaran</span>
        <div>
            {{ $expenses->links() }}
        </div>
    </div>
    @endif
</section>
@endsection
