@extends('layouts.admin')

@section('title', 'Ulasan & Testimoni Pelanggan')

@section('header-actions')
<a href="{{ route('admin.reviews.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Tambah Ulasan</span>
</a>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. SUMMARY METRICS TILES -->
    <section class="bg-white rounded-2xl p-6 shadow-sm">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
            <!-- Total Ulasan -->
            <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Ulasan</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</span>
                    <span class="text-[11px] text-slate-400">testimoni</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Klien konveksi & seragam</p>
            </div>

            <!-- Rata-Rata Rating -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Rata-Rata Rating</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-amber-500">{{ number_format($stats['avg_rating'], 1) }}</span>
                    <div class="flex items-center text-amber-400 text-xs gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star {{ $i <= round($stats['avg_rating']) ? 'text-amber-400' : 'text-slate-200' }}"></i>
                        @endfor
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Dari skala 5.0 bintang</p>
            </div>

            <!-- Tayang di Web (Live) -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tayang di Web (Live)</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ number_format($stats['published']) }}</span>
                    <span class="text-[11px] text-emerald-600/80 font-medium">terverifikasi</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Muncul di landing page</p>
            </div>

            <!-- Perlu Moderasi / Hidden -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Perlu Moderasi</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-slate-600">{{ number_format($stats['hidden']) }}</span>
                    <span class="text-[11px] text-slate-400 font-medium">disembunyikan</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Menunggu konfirmasi admin</p>
            </div>
        </div>
    </section>

    <!-- 2. TOOLBAR FILTER & SEARCH -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Status Tab Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs">
                <a href="{{ route('admin.reviews.index', request()->except('status')) }}" class="px-3.5 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !request('status') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Semua Ulasan ({{ $stats['total'] }})
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->query(), ['status' => 'published'])) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'published' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Tayang ({{ $stats['published'] }})
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->query(), ['status' => 'hidden'])) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'hidden' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Disembunyikan ({{ $stats['hidden'] }})
                </a>
            </div>

            <!-- Search & Rating Dropdown Filter -->
            <div class="flex items-center gap-2.5">
                <div class="relative w-full sm:w-64">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / isi testimoni..." class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                </div>

                <select name="rating" onchange="this.form.submit()" class="bg-slate-50 text-xs font-semibold text-slate-700 rounded-xl px-3 py-2 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 cursor-pointer shrink-0">
                    <option value="">Semua Bintang</option>
                    <option value="5" @selected(request('rating') == '5')>Bintang 5 ★★★★★</option>
                    <option value="4" @selected(request('rating') == '4')>Bintang 4 ★★★★☆</option>
                    <option value="3" @selected(request('rating') == '3')>Bintang 3 ★★★☆☆</option>
                </select>
            </div>

        </form>
    </section>

    <!-- 3. DATA TABLE ULASAN -->
    <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">NAMA</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">RATING</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">ULASAN</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">TANGGAL</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">STATUS</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($reviews as $review)
                        @php
                            $isPublished = $review->is_published;
                            $initial = mb_strtoupper(mb_substr($review->author_name, 0, 1));
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- 1. NAMA -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs shrink-0 border border-slate-200/60">
                                        {{ $initial }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.reviews.edit', $review) }}" class="font-bold text-slate-900 block hover:text-brand-700 transition-colors">
                                            {{ $review->author_name }}
                                        </a>
                                        <span class="text-[10px] text-slate-400 block">Google Review / Verified Client</span>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. RATING -->
                            <td class="py-3.5 px-5 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <div class="flex items-center text-amber-400 text-[11px]">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fa-solid fa-star {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-200' }}"></i>
                                        @endfor
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-700 ml-1 font-mono">{{ number_format($review->rating, 1) }}</span>
                                </div>
                            </td>

                            <!-- 3. ULASAN -->
                            <td class="py-3.5 px-5 max-w-sm whitespace-normal">
                                <p class="text-slate-600 line-clamp-2 leading-relaxed text-xs">
                                    "{{ $review->content }}"
                                </p>
                            </td>

                            <!-- 4. TANGGAL -->
                            <td class="py-3.5 px-5 text-center font-mono text-[11px] text-slate-500">
                                {{ $review->review_date?->format('d/m/Y') ?? '—' }}
                            </td>

                            <!-- 5. STATUS -->
                            <td class="py-3.5 px-5 text-center">
                                @if($isPublished)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Tayang
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 font-medium text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Disembunyikan
                                    </span>
                                @endif
                            </td>

                            <!-- 6. AKSI -->
                            <td class="py-3.5 px-5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.reviews.edit', $review) }}" title="Edit Ulasan" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-700 hover:bg-brand-50 transition-colors">
                                        <i class="fa-regular fa-pen-to-square text-xs"></i>
                                    </a>

                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Hapus ulasan dari {{ $review->author_name }}?')" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus Ulasan" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                            <i class="fa-regular fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-star text-2xl mb-2 block text-slate-300"></i>
                                Belum ada ulasan testimoni yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="p-4 sm:px-6 border-t border-slate-50 bg-white">
                {{ $reviews->links() }}
            </div>
        @endif
    </section>

</div>
@endsection
