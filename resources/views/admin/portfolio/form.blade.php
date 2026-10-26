@extends('layouts.admin')

@section('title', $portfolio->exists ? 'Edit Portfolio' : 'Portfolio Baru')

@section('content')
<div class="max-w-3xl space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.portfolio.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Portfolio</span>
        </a>
    </div>

    <form method="POST"
          action="{{ $portfolio->exists ? route('admin.portfolio.update', $portfolio) : route('admin.portfolio.store') }}"
          enctype="multipart/form-data" class="space-y-6"
          x-data="slugger(@js(old('title', $portfolio->title)), @js(old('slug', $portfolio->slug)), @js($portfolio->exists))">
        @csrf
        @if($portfolio->exists) @method('PUT') @endif

        <!-- CARD 1: INFORMASI PORTFOLIO -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $portfolio->exists ? 'Edit Karya Portfolio' : 'Tambah Karya Portfolio Baru' }}</h3>
                    <p class="text-xs text-slate-400">Dokumentasi hasil jahitan dan karya produksi konveksi Zada Karya</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="title">
                        Judul Portfolio <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="title" name="title" x-model="title" @input="syncSlug()" required placeholder="Contoh: Kemeja Lapangan PDL Kompas TV">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="slug">
                        Slug URL (Otomatis)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none font-mono"
                           type="text" id="slug" name="slug" x-model="slug" @input="touchSlug()" placeholder="otomatis-dari-judul">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="portfolio_category_id">
                        Kategori Produk
                    </label>
                    <select class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                            id="portfolio_category_id" name="portfolio_category_id">
                        <option value="">— Pilih Kategori —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('portfolio_category_id', $portfolio->portfolio_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="production_year">
                        Tahun Produksi
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none font-mono"
                           type="text" id="production_year" name="production_year" value="{{ old('production_year', $portfolio->production_year) }}" placeholder="{{ date('Y') }}">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="description">
                        Deskripsi Cerita Pengerjaan
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="description" name="description" rows="4" placeholder="Ceritakan detail bahan, jenis sablon/bordir, serta tantangan pengerjaan...">{{ old('description', $portfolio->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client_name">
                        Nama Klien / Instansi (Opsional)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="client_name" name="client_name" value="{{ old('client_name', $portfolio->client_name) }}" placeholder="Contoh: BEM UI / Kompas Gramedia">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="tags_text">
                        Tags Kata Kunci (Pisahkan koma)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="tags_text" name="tags_text" value="{{ old('tags_text', implode(', ', $portfolio->tags ?? [])) }}" placeholder="drill, kemeja, bordir komputer">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="cover_image">
                        Foto Cover Utama
                    </label>
                    <x-admin.media-picker name="cover_image" :current="$portfolio->cover_image">
                        <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="" class="h-24 w-auto rounded-xl object-cover">
                    </x-admin.media-picker>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="gallery">
                        Foto Tambahan Galeri (Bisa Lebih Dari Satu)
                    </label>
                    <x-admin.media-picker name="gallery" :multiple="true" />
                    <p class="mt-1 text-[11px] text-slate-400">Thumbnail galeri otomatis di-generate secara responsif.</p>
                </div>
            </div>

            @if($portfolio->exists && $portfolio->images->isNotEmpty())
                <div class="mt-6 border-t border-slate-100 pt-6">
                    <p class="block text-xs font-semibold text-slate-700 mb-3">Foto Galeri Saat Ini</p>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach($portfolio->images as $image)
                            <div class="relative group rounded-xl overflow-hidden border border-slate-200">
                                <img src="{{ asset('storage/'.($image->thumb_path ?: $image->image_path)) }}" alt="" class="aspect-square w-full object-cover">
                                <button type="submit"
                                        form="delete-image-{{ $image->id }}"
                                        onclick="return confirm('Hapus gambar ini?')"
                                        class="absolute right-1.5 top-1.5 w-6 h-6 rounded-lg bg-white/90 text-rose-600 flex items-center justify-center shadow-xs hover:bg-rose-50 transition-colors">
                                    <i class="fa-solid fa-xmark text-[10px]"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- CARD 2: SEO & STATUS -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">SEO &amp; Status Publikasi</h3>
                    <p class="text-xs text-slate-400">Optimasi mesin pencari dan opsi tampil di halaman utama</p>
                </div>
            </div>

            <div class="grid gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="seo_title">
                        SEO Title (Meta Judul)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $portfolio->seo_title) }}" placeholder="Judul pada mesin pencari Google">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="seo_description">
                        SEO Description (Meta Deskripsi)
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="seo_description" name="seo_description" rows="2" placeholder="Deskripsi singkat untuk snippet Google...">{{ old('seo_description', $portfolio->seo_description) }}</textarea>
                </div>

                <div class="flex flex-wrap gap-6 pt-2">
                    <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $portfolio->is_published ?? true)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                        <span>Publikasikan Karya Ini</span>
                    </label>

                    <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $portfolio->is_featured)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                        <span>Jadikan Featured (Tampil di Beranda Utama)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.portfolio.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                <i class="fa-solid fa-check text-[11px]"></i>
                <span>{{ $portfolio->exists ? 'Simpan Perubahan' : 'Buat Portfolio' }}</span>
            </button>
        </div>
    </form>
</div>

@if($portfolio->exists)
    @foreach($portfolio->images as $image)
        <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('admin.portfolio.images.destroy', $image) }}">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif
@endsection
