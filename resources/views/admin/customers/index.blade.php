@extends('layouts.admin')

@section('title', 'Daftar Customer')

@section('header-actions')
<a href="{{ route('admin.customers.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Customer Baru</span>
</a>
@endsection

@section('content')
<!-- 1. SUMMARY METRICS TILES -->
<section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    <!-- Total Customer -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Customer</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $stats['total'] }}</span>
            <span class="text-[11px] text-slate-400">klien</span>
        </div>
    </div>

    <!-- Customer Repeat Order -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Repeat Order</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ $stats['repeat'] }}</span>
            <span class="text-[11px] text-emerald-600/80 font-medium">≥ 2 pesanan</span>
        </div>
    </div>

    <!-- Instansi / Korporat -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Perusahaan / B2B</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $stats['b2b'] }}</span>
            <span class="text-[11px] text-slate-400 font-medium">organisasi/PT</span>
        </div>
    </div>

    <!-- Total Nilai Transaksi -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Nilai Pesanan</p>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="text-xl sm:text-2xl font-bold text-slate-900 truncate">{{ rupiah($stats['total_spent']) }}</span>
        </div>
    </div>
</section>

<!-- 2. FILTER & ACTION TOOLBAR -->
<section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs no-scrollbar">
            <a href="{{ route('admin.customers.index') }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !$tab ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Semua Customer ({{ $stats['total'] }})
            </a>
            <a href="{{ route('admin.customers.index', ['tab' => 'repeat']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $tab === 'repeat' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Repeat Order ({{ $stats['repeat'] }})
            </a>
            <a href="{{ route('admin.customers.index', ['tab' => 'instansi']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $tab === 'instansi' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Perusahaan / Organisasi ({{ $stats['b2b'] }})
            </a>
        </div>

        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full lg:w-auto">
            @if($tab)<input type="hidden" name="tab" value="{{ $tab }}">@endif

            <!-- Search Input -->
            <div class="relative flex-1 sm:w-64 sm:flex-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari Nama / Perusahaan / Kota..."
                       class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
            </div>

            <!-- Kota Filter Dropdown -->
            @if($cities->isNotEmpty())
            <select name="city" onchange="this.form.submit()" class="bg-slate-50 text-xs text-slate-600 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-slate-300">
                <option value="all">Semua Kota</option>
                @foreach($cities as $c)
                    <option value="{{ $c }}" @selected(request('city') === $c)>{{ $c }}</option>
                @endforeach
            </select>
            @endif

            <button type="submit" class="shrink-0 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Cari
            </button>
            @if(request()->hasAny(['q', 'tab', 'city']))
            <a href="{{ route('admin.customers.index') }}" class="shrink-0 px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors">
                Reset
            </a>
            @endif
        </form>

    </div>
</section>

<!-- 3. DATA TABLE CUSTOMER -->
<section class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
            <thead>
                <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal">NAMA</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal">PERUSAHAAN</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal">WHATSAPP</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal">KOTA</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal text-center">PESANAN</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal text-right">TOTAL TRANSAKSI</th>
                    <th class="py-3.5 px-3.5 sm:px-5 font-normal text-center">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($customers as $customer)
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3.5 px-5">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold text-slate-900 block hover:text-brand-600 transition-colors">
                            {{ $customer->name }}
                        </a>
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="text-slate-600">{{ $customer->company ?? '—' }}</span>
                    </td>
                    <td class="py-3.5 px-5">
                        @if($customer->whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->whatsapp) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 text-slate-700 hover:text-emerald-600 transition-colors">
                                <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                <span>{{ $customer->whatsapp }}</span>
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="text-slate-600">{{ $customer->city ?? '—' }}</span>
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $customer->orders_count >= 2 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $customer->orders_count }} order
                        </span>
                    </td>
                    <td class="py-3.5 px-5 font-semibold text-slate-900 text-right">
                        {{ rupiah($customer->total_spent ?? 0) }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.customers.show', $customer) }}" title="Lihat Profil"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-regular fa-eye text-xs"></i>
                            </a>
                            <a href="{{ route('admin.customers.edit', $customer) }}" title="Edit Customer"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                        Tidak ada data customer yang cocok.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer Pagination -->
    @if($customers->hasPages() || $customers->total() > 0)
    <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
        <span>Menampilkan {{ $customers->count() }} dari {{ $customers->total() }} customer</span>
        <div>
            {{ $customers->links() }}
        </div>
    </div>
    @endif
</section>
@endsection
