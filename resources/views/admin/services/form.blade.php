@extends('layouts.admin')

@section('title', $service->exists ? 'Edit Layanan' : 'Layanan Baru')

@section('content')
<div class="max-w-3xl space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Layanan</span>
        </a>
    </div>

    <form method="POST"
          action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}"
          enctype="multipart/form-data" class="space-y-6"
          x-data="slugger(@js(old('name', $service->name)), @js(old('slug', $service->slug)), @js($service->exists))">
        @csrf
        @if($service->exists) @method('PUT') @endif

        <!-- CARD 1: INFORMASI LAYANAN -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-scissors"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $service->exists ? 'Edit Informasi Layanan' : 'Tambah Layanan Konveksi Baru' }}</h3>
                    <p class="text-xs text-slate-400">Deskripsi jasa konveksi, spesifikasi bahan, minimum order, dan foto showcase</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                        Nama Layanan <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="name" name="name" x-model="title" @input="syncSlug()" required placeholder="Contoh: Konveksi Kemeja Korsa PDL">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="slug">
                        Slug URL (Otomatis)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none font-mono"
                           type="text" id="slug" name="slug" x-model="slug" @input="touchSlug()" placeholder="otomatis-dari-nama">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="short_description">
                        Deskripsi Singkat (Ringkasan di Katalog)
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="short_description" name="short_description" rows="2" maxlength="500" placeholder="Ringkasan 1-2 kalimat untuk kartu layanan...">{{ old('short_description', $service->short_description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="description">
                        Deskripsi Lengkap (HTML Diperbolehkan)
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="description" name="description" rows="8">{{ old('description', $service->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="features_text">
                        Keunggulan / Fitur Layanan (Satu per baris)
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="features_text" name="features_text" rows="4" placeholder="Bahan drill berkualitas premium&#10;Bordir komputer presisi tinggi&#10;Jahitan rantai ganda tahan lama">{{ old('features_text', implode("\n", $service->features ?? [])) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="material_info">
                        Informasi Bahan Kain
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="material_info" name="material_info" rows="3" placeholder="Pilihan bahan: American Drill, Japan Drill, Taipan Drill...">{{ old('material_info', $service->material_info) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="production_info">
                        Informasi Waktu & Proses Produksi
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="production_info" name="production_info" rows="3" placeholder="Estimasi pengerjaan 10-14 hari kerja, quality control ketat...">{{ old('production_info', $service->production_info) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="min_order">
                        Minimum Order (MOQ)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="min_order" name="min_order" value="{{ old('min_order', $service->min_order) }}" placeholder="Contoh: 24 pcs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="sort_order">
                        Urutan Tampil (Sort Order)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none font-mono"
                           type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="featured_image">
                        Foto / Sampul Utama Layanan
                    </label>
                    <x-admin.media-picker name="featured_image" :current="$service->featured_image">
                        <img src="{{ asset('storage/'.$service->featured_image) }}" alt="" class="h-24 w-auto rounded-xl object-cover">
                    </x-admin.media-picker>
                </div>
            </div>
        </div>

        <!-- CARD 2: SEO & STATUS -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Optimasi Mesin Pencari (SEO) & Publikasi</h3>
                    <p class="text-xs text-slate-400">Meta tags Google Search dan status publikasi halaman di website</p>
                </div>
            </div>

            <div class="grid gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="seo_title">
                        SEO Title (Meta Judul)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $service->seo_title) }}" placeholder="Judul yang muncul di Google">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="seo_description">
                        SEO Description (Meta Deskripsi)
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="seo_description" name="seo_description" rows="2" placeholder="Deskripsi singkat yang tampil pada hasil pencarian Google...">{{ old('seo_description', $service->seo_description) }}</textarea>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $service->is_published ?? true)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                        <span>Publikasikan layanan ini di halaman publik website</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.services.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                <i class="fa-solid fa-check text-[11px]"></i>
                <span>{{ $service->exists ? 'Simpan Perubahan' : 'Buat Layanan' }}</span>
            </button>
        </div>
    </form>
</div>
@endsection
