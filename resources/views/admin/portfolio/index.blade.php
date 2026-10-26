@extends('layouts.admin')

@section('title', 'Katalog Portfolio Hasil Produksi')

@section('header-actions')
<a href="{{ route('admin.portfolio.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Tambah Karya</span>
</a>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. SUMMARY METRICS TILES -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total Karya -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Karya</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</span>
                <span class="text-[11px] text-slate-400">foto</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Dokumentasi produksi</p>
        </div>

        <!-- Tayang (Published) -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tayang (Published)</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ number_format($stats['published']) }}</span>
                <span class="text-[11px] text-emerald-600/80 font-medium truncate">live di website</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Dilihat calon klien</p>
        </div>

        <!-- Draft / Arsip -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Draft / Belum Tayang</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-amber-600">{{ number_format($stats['draft']) }}</span>
                <span class="text-[11px] text-amber-600/80 font-medium truncate">pending rilis</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Menunggu approval</p>
        </div>

        <!-- Kategori Pakaian -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Kategori Pakaian</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['categories_count']) }}</span>
                <span class="text-[11px] text-slate-400">segmen</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Kaos, PDH, Jaket, dsb</p>
        </div>
    </section>

    <!-- 2. MAIN 2-COLUMN MODULAR LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- SIDEBAR SAMPING: KATEGORI & JUMLAH PER KATEGORI -->
        <div class="lg:col-span-4 xl:col-span-3 space-y-4">
            
            <section class="bg-white rounded-2xl p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Kategori Portofolio</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Filter berdasarkan segmen produk</p>
                    </div>
                    @if(request('category_id'))
                        <a href="{{ route('admin.portfolio.index', request()->except('category_id')) }}" class="text-[11px] text-brand-700 hover:text-brand-900 font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </div>

                <!-- List Kategori Interaktif -->
                <div class="space-y-1 text-xs">
                    @php
                        $isAllActive = !request('category_id');
                    @endphp
                    <a href="{{ route('admin.portfolio.index', request()->except('category_id')) }}" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition-colors {{ $isAllActive ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-shapes text-xs {{ $isAllActive ? 'text-slate-300' : 'text-slate-400' }}"></i>
                            <span>Semua Kategori</span>
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isAllActive ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600' }} font-semibold">
                            {{ $stats['total'] }}
                        </span>
                    </a>

                    @foreach($categories as $category)
                        @php
                            $isActive = (string)request('category_id') === (string)$category->id;
                            $catIcon = 'fa-tag';
                            $cName = strtolower($category->name);
                            if (str_contains($cName, 'kaos')) $catIcon = 'fa-shirt';
                            elseif (str_contains($cName, 'jas') || str_contains($cName, 'almet')) $catIcon = 'fa-user-tie';
                            elseif (str_contains($cName, 'kemeja') || str_contains($cName, 'pdh')) $catIcon = 'fa-clipboard-user';
                            elseif (str_contains($cName, 'jaket') || str_contains($cName, 'hoodie')) $catIcon = 'fa-vest-patches';
                            elseif (str_contains($cName, 'polo')) $catIcon = 'fa-vest';
                            elseif (str_contains($cName, 'rompi')) $catIcon = 'fa-shield';
                        @endphp
                        <div class="flex items-center group">
                            <a href="{{ route('admin.portfolio.index', array_merge(request()->query(), ['category_id' => $category->id])) }}" class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-colors {{ $isActive ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                <span class="flex items-center gap-2 truncate pr-2">
                                    <i class="fa-solid {{ $catIcon }} text-xs {{ $isActive ? 'text-slate-300' : 'text-slate-400' }}"></i>
                                    <span class="truncate">{{ $category->name }}</span>
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isActive ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600' }} font-semibold shrink-0">
                                    {{ $category->portfolios_count }}
                                </span>
                            </a>
                            <form method="POST" action="{{ route('admin.portfolio-categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori {{ $category->name }}?')" class="opacity-0 group-hover:opacity-100 transition-opacity ml-1">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 text-xs" title="Hapus Kategori">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <!-- Quick Info Card -->
                <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-400 leading-relaxed">
                    <i class="fa-solid fa-circle-info text-slate-300 mr-1"></i>
                    Klik kategori di atas untuk menyaring katalog karya secara instan.
                </div>
            </section>

            <!-- Quick Add Category Form -->
            <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm space-y-2">
                <div>
                    <span class="font-bold text-xs text-slate-800 block">Kategori Baru?</span>
                    <span class="text-[11px] text-slate-400">Tambahkan segmen produk baru</span>
                </div>
                <form method="POST" action="{{ route('admin.portfolio-categories.store') }}" class="flex gap-2 pt-1">
                    @csrf
                    <input type="text" name="name" placeholder="Nama kategori..." required class="flex-1 bg-slate-50 text-xs rounded-xl px-3 py-2 text-slate-700 placeholder-slate-400 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    <button type="submit" class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-colors shrink-0">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                    </button>
                </form>
            </div>

        </div>

        <!-- AREA UTAMA: TOOLBAR & DATA TABLE PORTOFOLIO -->
        <div class="lg:col-span-8 xl:col-span-9 space-y-4">
            
            <!-- Toolbar Search & Filter -->
            <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                <form method="GET" action="{{ route('admin.portfolio.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif

                    <!-- Status Tab Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs no-scrollbar">
                        <a href="{{ route('admin.portfolio.index', array_merge(request()->except('status'), [])) }}" class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !request('status') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Semua ({{ $stats['total'] }})
                        </a>
                        <a href="{{ route('admin.portfolio.index', array_merge(request()->query(), ['status' => 'published'])) }}" class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'published' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Published ({{ $stats['published'] }})
                        </a>
                        <a href="{{ route('admin.portfolio.index', array_merge(request()->query(), ['status' => 'draft'])) }}" class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'draft' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Draft ({{ $stats['draft'] }})
                        </a>
                    </div>

                    <!-- Right Search & Year Filter -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <div class="relative flex-1 sm:w-56 sm:flex-none">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul / klien..." class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        </div>

                        <select name="year" onchange="this.form.submit()" class="bg-slate-50 text-xs font-semibold text-slate-700 rounded-xl px-3 py-2 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 cursor-pointer shrink-0">
                            <option value="">Semua Tahun</option>
                            @foreach($years as $yr)
                                <option value="{{ $yr }}" @selected(request('year') == $yr)>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </section>

            <!-- 3. DATA TABLE PORTOFOLIO -->
            <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead>
                            <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">JUDUL KARYA</th>
                                <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">KATEGORI</th>
                                <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">TAHUN</th>
                                <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">STATUS</th>
                                <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($portfolios as $portfolio)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <!-- 1. JUDUL KARYA -->
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            @if($portfolio->cover_image)
                                                <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="{{ $portfolio->title }}" class="w-10 h-10 rounded-xl object-cover bg-slate-100 shrink-0 border border-slate-100">
                                            @else
                                                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center font-bold text-sm shrink-0 border border-brand-100">
                                                    {{ mb_substr($portfolio->title, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="space-y-0.5 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <a href="{{ route('admin.portfolio.edit', $portfolio) }}" class="font-bold text-slate-900 block hover:text-brand-700 transition-colors truncate max-w-xs sm:max-w-md">
                                                        {{ $portfolio->title }}
                                                    </a>
                                                    @if($portfolio->is_featured)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
                                                            Featured
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="text-[11px] text-slate-400 block">
                                                    Klien: <strong class="text-slate-600 font-medium">{{ $portfolio->client_name ?: '—' }}</strong>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. KATEGORI -->
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            {{ $portfolio->category?->name ?? 'Tanpa Kategori' }}
                                        </span>
                                    </td>

                                    <!-- 3. TAHUN -->
                                    <td class="py-3.5 px-5 text-center font-mono font-medium text-slate-700">
                                        {{ $portfolio->production_year ?: '—' }}
                                    </td>

                                    <!-- 4. STATUS -->
                                    <td class="py-3.5 px-5 text-center">
                                        @if($portfolio->is_published)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Published
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold text-[10px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Draft
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 5. AKSI -->
                                    <td class="py-3.5 px-5 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ route('portfolio.show', $portfolio) }}" target="_blank" title="Lihat di Web" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                                                <i class="fa-regular fa-eye text-xs"></i>
                                            </a>

                                            <a href="{{ route('admin.portfolio.edit', $portfolio) }}" title="Edit Portofolio" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-700 hover:bg-brand-50 transition-colors">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </a>

                                            <form method="POST" action="{{ route('admin.portfolio.destroy', $portfolio) }}" onsubmit="return confirm('Hapus portofolio ini beserta semua gambarnya?')" class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Hapus Portofolio" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        <i class="fa-regular fa-images text-3xl mb-2 block text-slate-300"></i>
                                        Belum ada portofolio yang cocok dengan kriteria pencarian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer Pagination -->
                @if($portfolios->hasPages())
                    <div class="p-4 sm:px-6 border-t border-slate-50 bg-white">
                        {{ $portfolios->links() }}
                    </div>
                @endif
            </section>

        </div>

    </div>

</div>
@endsection
