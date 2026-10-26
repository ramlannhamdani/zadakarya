@extends('layouts.admin')

@section('title', $review->exists ? 'Edit Ulasan' : 'Tambah Ulasan')

@section('content')
<div class="max-w-2xl space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reviews.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Ulasan</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
        <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold shrink-0">
                <i class="fa-solid fa-star"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $review->exists ? 'Edit Ulasan Klien' : 'Tambah Ulasan Google Maps Baru' }}</h3>
                <p class="text-xs text-slate-400">Testimoni kepuasan pelanggan untuk ditampilkan di halaman depan website</p>
            </div>
        </div>

        <form method="POST" action="{{ $review->exists ? route('admin.reviews.update', $review) : route('admin.reviews.store') }}" class="space-y-5">
            @csrf
            @if($review->exists) @method('PUT') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="author_name">
                        Nama Pengulas <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="author_name" name="author_name" value="{{ old('author_name', $review->author_name) }}" required placeholder="Contoh: Rian Anggoro">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="rating">
                        Rating Bintang <span class="text-rose-500">*</span>
                    </label>
                    <select class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                            id="rating" name="rating">
                        @foreach([5, 4, 3, 2, 1] as $r)
                            <option value="{{ $r }}" @selected(old('rating', $review->rating ?? 5) == $r)>{{ $r }} ★ — {{ str_repeat('★', $r) }}{{ str_repeat('☆', 5 - $r) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="content">
                        Isi Teks Ulasan / Testimoni <span class="text-rose-500">*</span>
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="content" name="content" rows="4" required placeholder="Salin kutipan teks ulasan asli dari Google Maps...">{{ old('content', $review->content) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="review_date">
                        Tanggal Ulasan
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="date" id="review_date" name="review_date" value="{{ old('review_date', $review->review_date?->toDateString()) }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="sort_order">
                        Urutan Tampil (Sort Order)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none font-mono"
                           type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $review->sort_order ?? 0) }}">
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $review->is_published ?? true)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    <span>Tampilkan ulasan ini pada website publik</span>
                </label>
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.reviews.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                    <i class="fa-solid fa-check text-[11px]"></i>
                    <span>{{ $review->exists ? 'Simpan Perubahan' : 'Tambah Ulasan' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
