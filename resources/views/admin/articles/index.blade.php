@extends('layouts.admin')

@section('title', 'Manajemen Artikel & Edukasi Blog')

@section('header-actions')
<a href="{{ route('admin.articles.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Tulis Artikel Baru</span>
</a>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. SUMMARY METRICS TILES -->
    <section class="bg-white rounded-2xl p-6 shadow-sm">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
            <!-- Total Artikel -->
            <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Artikel</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($statusCounts['all'] ?? 0) }}</span>
                    <span class="text-[11px] text-slate-400">postingan</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Konten artikel website</p>
            </div>

            <!-- Tayang (Published) -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tayang (Published)</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ number_format($statusCounts['published'] ?? 0) }}</span>
                    <span class="text-[11px] text-emerald-600/80 font-medium">bisa dibaca publik</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Live di halaman blog</p>
            </div>

            <!-- Konsep (Draft) -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Konsep (Draft)</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-amber-600">{{ number_format($statusCounts['draft'] ?? 0) }}</span>
                    <span class="text-[11px] text-amber-600/80 font-medium">dalam penulisan</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Belum dipublikasikan</p>
            </div>

            <!-- Total Kategori Blog -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Kategori Topik</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-brand-700">{{ number_format($categories->count()) }}</span>
                    <span class="text-[11px] text-slate-400 font-medium">rubrik edukasi</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Bahan, sablon, seragam, dll</p>
            </div>
        </div>
    </section>

    <!-- 2. MAIN 2-COLUMN LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- SIDEBAR KATEGORI ARTIKEL (3 COLS) -->
        <div class="lg:col-span-4 xl:col-span-3 space-y-4">
            
            <section class="bg-white rounded-2xl p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Kategori Artikel</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Jumlah artikel per rubrik</p>
                    </div>
                    @if(request('kategori'))
                        <a href="{{ route('admin.articles.index', request()->except('kategori')) }}" class="text-[11px] text-brand-700 hover:text-brand-900 font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </div>

                <!-- List Rubrik Kategori -->
                <div class="space-y-1 text-xs">
                    @php
                        $isAllActive = !request('kategori');
                    @endphp
                    <a href="{{ route('admin.articles.index', request()->except('kategori')) }}" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition-colors {{ $isAllActive ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-shapes text-xs {{ $isAllActive ? 'text-slate-300' : 'text-slate-400' }}"></i>
                            <span>Semua Kategori</span>
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isAllActive ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600' }} font-semibold">
                            {{ $statusCounts['all'] ?? 0 }}
                        </span>
                    </a>

                    @foreach($categories as $category)
                        @php
                            $isActive = request('kategori') === $category->slug;
                            $catIcon = 'fa-tag';
                            $cName = strtolower($category->name);
                            if (str_contains($cName, 'bahan') || str_contains($cName, 'kain')) $catIcon = 'fa-cubes';
                            elseif (str_contains($cName, 'sablon') || str_contains($cName, 'bordir')) $catIcon = 'fa-paint-roller';
                            elseif (str_contains($cName, 'tips') || str_contains($cName, 'panduan')) $catIcon = 'fa-lightbulb';
                            elseif (str_contains($cName, 'berita') || str_contains($cName, 'event')) $catIcon = 'fa-bullhorn';
                            elseif (str_contains($cName, 'seragam') || str_contains($cName, 'korporat')) $catIcon = 'fa-briefcase';
                        @endphp
                        <div class="flex items-center group">
                            <a href="{{ route('admin.articles.index', array_merge(request()->query(), ['kategori' => $category->slug])) }}" class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-colors {{ $isActive ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                <span class="flex items-center gap-2 truncate pr-2">
                                    <i class="fa-solid {{ $catIcon }} text-xs {{ $isActive ? 'text-slate-300' : 'text-slate-400' }}"></i>
                                    <span class="truncate">{{ $category->name }}</span>
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isActive ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600' }} font-semibold shrink-0">
                                    {{ $category->articles_count }}
                                </span>
                            </a>
                            <form method="POST" action="{{ route('admin.article-categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori {{ $category->name }}?')" class="opacity-0 group-hover:opacity-100 transition-opacity ml-1">
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
                    Klik salah satu kategori untuk menyaring artikel secara instan.
                </div>
            </section>

            <!-- Quick Add Category Form -->
            <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm space-y-2">
                <div>
                    <span class="font-bold text-xs text-slate-800 block">Kategori Baru?</span>
                    <span class="text-[11px] text-slate-400">Tambah rubrik topik blog</span>
                </div>
                <form method="POST" action="{{ route('admin.article-categories.store') }}" class="flex gap-2 pt-1">
                    @csrf
                    <input type="text" name="name" placeholder="Nama rubrik..." required class="flex-1 bg-slate-50 text-xs rounded-xl px-3 py-2 text-slate-700 placeholder-slate-400 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    <button type="submit" class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-colors shrink-0">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                    </button>
                </form>
            </div>

        </div>

        <!-- AREA UTAMA: TOOLBAR & TABEL ARTIKEL (9 COLS) -->
        <div class="lg:col-span-8 xl:col-span-9 space-y-4">
            
            <!-- Toolbar Filter & Search -->
            <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                <form method="GET" action="{{ route('admin.articles.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    @if(request('kategori'))
                        <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                    @endif

                    <!-- Status Tab Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs">
                        <a href="{{ route('admin.articles.index', request()->except('status')) }}" class="px-3.5 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !$currentStatus ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Semua ({{ $statusCounts['all'] ?? 0 }})
                        </a>
                        <a href="{{ route('admin.articles.index', array_merge(request()->query(), ['status' => 'published'])) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ $currentStatus === 'published' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Publish ({{ $statusCounts['published'] ?? 0 }})
                        </a>
                        @if(($statusCounts['scheduled'] ?? 0) > 0)
                            <a href="{{ route('admin.articles.index', array_merge(request()->query(), ['status' => 'scheduled'])) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ $currentStatus === 'scheduled' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                Dijadwalkan ({{ $statusCounts['scheduled'] }})
                            </a>
                        @endif
                        <a href="{{ route('admin.articles.index', array_merge(request()->query(), ['status' => 'draft'])) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ $currentStatus === 'draft' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Draft ({{ $statusCounts['draft'] ?? 0 }})
                        </a>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul artikel / tag..." class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    </div>
                </form>
            </section>

            <!-- 3. DATA TABLE BLOG -->
            <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead>
                            <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                <th class="py-3.5 px-5 font-normal uppercase tracking-wider">JUDUL ARTIKEL</th>
                                <th class="py-3.5 px-5 font-normal uppercase tracking-wider">KATEGORI</th>
                                <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">TERBIT</th>
                                <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">STATUS</th>
                                <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($articles as $article)
                                @php
                                    $st = $article->status;
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <!-- 1. JUDUL ARTIKEL -->
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            @if($article->featured_image)
                                                <img src="{{ asset('storage/'.$article->featured_image) }}" alt="{{ $article->title }}" class="w-10 h-10 rounded-xl object-cover bg-slate-100 shrink-0 border border-slate-100">
                                            @else
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0 border border-slate-200/60">
                                                    <i class="fa-regular fa-newspaper"></i>
                                                </div>
                                            @endif
                                            <div class="space-y-0.5 max-w-sm sm:max-w-md">
                                                <a href="{{ route('admin.articles.edit', $article) }}" class="font-bold text-slate-900 block hover:text-brand-700 transition-colors truncate" title="{{ $article->title }}">
                                                    {{ $article->title }}
                                                </a>
                                                <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono">
                                                    <span class="truncate">/blog/{{ $article->slug }}</span>
                                                    <span>•</span>
                                                    <span><i class="fa-regular fa-user mr-0.5"></i> {{ $article->author?->name ?? 'Admin' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. KATEGORI -->
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            {{ $article->category?->name ?? 'Tanpa Kategori' }}
                                        </span>
                                    </td>

                                    <!-- 3. TERBIT -->
                                    <td class="py-3.5 px-5 text-center font-mono font-medium text-slate-600">
                                        {{ $article->published_at?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <!-- 4. STATUS -->
                                    <td class="py-3.5 px-5 text-center">
                                        @if($st === 'published')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Publish
                                            </span>
                                        @elseif($st === 'scheduled')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold text-[10px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                Dijadwalkan
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
                                            <a href="{{ route('blog.show', $article) }}" target="_blank" title="Lihat di Web" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                                                <i class="fa-regular fa-eye text-xs"></i>
                                            </a>

                                            <a href="{{ route('admin.articles.edit', $article) }}" title="Edit Artikel" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-700 hover:bg-brand-50 transition-colors">
                                                <i class="fa-regular fa-pen-to-square text-xs"></i>
                                            </a>

                                            <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Hapus artikel {{ $article->title }}?')" class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Hapus Artikel" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        <i class="fa-regular fa-newspaper text-2xl mb-2 block text-slate-300"></i>
                                        Belum ada artikel yang cocok dengan filter atau kata kunci.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($articles->hasPages())
                    <div class="p-4 sm:px-6 border-t border-slate-50 bg-white">
                        {{ $articles->links() }}
                    </div>
                @endif
            </section>

        </div>

    </div>

</div>
@endsection
