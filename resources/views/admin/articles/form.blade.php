@extends('layouts.admin')

@section('title', $article->exists ? 'Edit Artikel' : 'Artikel Baru')

@section('content')
<div class="max-w-3xl space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Artikel &amp; Blog</span>
        </a>
    </div>

    <form method="POST"
          action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
          enctype="multipart/form-data" class="space-y-6"
          x-data="slugger(@js(old('title', $article->title)), @js(old('slug', $article->slug)), @js($article->exists))">
        @csrf
        @if($article->exists) @method('PUT') @endif

        <!-- CARD 1: INFORMASI ARTIKEL -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-newspaper"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $article->exists ? 'Edit Tulisan Artikel' : 'Tulis Artikel Blog Baru' }}</h3>
                    <p class="text-xs text-slate-400">Edukasi kain, tips pakaian custom, dan panduan produksi konveksi Zada Karya</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="title">
                        Judul Artikel <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="title" name="title" x-model="title" @input="syncSlug()" required placeholder="Contoh: Mengenal Perbedaan Bahan Drill American vs Japan Drill">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="slug">
                        Slug URL (Otomatis)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none font-mono"
                           type="text" id="slug" name="slug" x-model="slug" @input="touchSlug()" placeholder="otomatis-dari-judul">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="article_category_id">
                        Kategori Artikel
                    </label>
                    <select class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                            id="article_category_id" name="article_category_id">
                        <option value="">— Pilih Kategori —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('article_category_id', $article->article_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="excerpt">
                        Ringkasan Singkat (Excerpt)
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="excerpt" name="excerpt" rows="2" maxlength="500" placeholder="Ringkasan isi artikel untuk ditampilkan di kartu artikel...">{{ old('excerpt', $article->excerpt) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="content">
                        Konten Lengkap Artikel <span class="text-rose-500">*</span>
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="content" name="content" rows="16" required
                              placeholder="Tulis konten artikel. Tag HTML sederhana diperbolehkan: <h2>, <p>, <ul>, <ol>, <strong>, <a>...">{{ old('content', $article->content) }}</textarea>
                    <p class="mt-1.5 text-[11px] text-slate-400">Gunakan tag HTML sederhana untuk format teks: <code class="rounded bg-slate-100 px-1 py-0.5">&lt;h2&gt;Judul&lt;/h2&gt;</code> <code class="rounded bg-slate-100 px-1 py-0.5">&lt;p&gt;Paragraf&lt;/p&gt;</code> <code class="rounded bg-slate-100 px-1 py-0.5">&lt;ul&gt;&lt;li&gt;Poin&lt;/li&gt;&lt;/ul&gt;</code></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="tags_text">
                        Tags Kata Kunci (Pisahkan koma)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="tags_text" name="tags_text" value="{{ old('tags_text', implode(', ', $article->tags ?? [])) }}" placeholder="tips kain, seragam kantor, bordir">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="featured_image">
                        Foto Sampul Utama (Featured Image)
                    </label>
                    <x-admin.media-picker name="featured_image" :current="$article->featured_image">
                        <img src="{{ asset('storage/'.$article->featured_image) }}" alt="" class="h-20 w-auto rounded-xl object-cover">
                    </x-admin.media-picker>
                </div>
            </div>
        </div>

        <!-- CARD 2: SEO & STATUS PUBLIKASI -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">SEO &amp; Status Publikasi</h3>
                    <p class="text-xs text-slate-400">Atur jadwal rilis artikel dan optimasi mesin pencari Google</p>
                </div>
            </div>

            <div class="grid gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="seo_title">
                        SEO Title (Meta Judul)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $article->seo_title) }}" placeholder="Judul artikel di Google">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="seo_description">
                        SEO Description (Meta Deskripsi)
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="seo_description" name="seo_description" rows="2" placeholder="Deskripsi ringkas yang tampil pada hasil pencarian...">{{ old('seo_description', $article->seo_description) }}</textarea>
                </div>

                <div class="pt-2 border-t border-slate-100" x-data="{
                    pubStatus: '{{ old('status_action', $article->published_at ? ($article->published_at->isFuture() ? 'schedule' : 'publish_now') : 'draft') }}'
                }">
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Status Jadwal Publikasi</label>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3.5 text-xs transition-all"
                               :class="pubStatus === 'publish_now' ? 'border-emerald-500 bg-emerald-50/50 font-bold text-emerald-950' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/60 text-slate-700'">
                            <input type="radio" name="status_action" value="publish_now" x-model="pubStatus" class="text-emerald-600 focus:ring-emerald-600">
                            <span>Terbitkan Sekarang</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3.5 text-xs transition-all"
                               :class="pubStatus === 'schedule' ? 'border-amber-500 bg-amber-50/50 font-bold text-amber-950' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/60 text-slate-700'">
                            <input type="radio" name="status_action" value="schedule" x-model="pubStatus" class="text-amber-600 focus:ring-amber-600">
                            <span>Jadwalkan Terbit</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3.5 text-xs transition-all"
                               :class="pubStatus === 'draft' ? 'border-slate-400 bg-slate-200/50 font-bold text-slate-900' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/60 text-slate-700'">
                            <input type="radio" name="status_action" value="draft" x-model="pubStatus" class="text-slate-600 focus:ring-slate-600">
                            <span>Simpan Draft</span>
                        </label>
                    </div>

                    <div class="mt-4" x-show="pubStatus === 'schedule'" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="published_at">Waktu Terbit Terjadwal (WIB)</label>
                        <input class="w-full sm:w-72 rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                               type="datetime-local" id="published_at" name="published_at"
                               value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}">
                        <p class="mt-1 text-[11px] text-slate-400">Artikel akan otomatis tayang di blog publik sesuai waktu yang ditentukan.</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $article->is_featured)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                            <span>Artikel Unggulan (Tampil Prioritas di Halaman Blog)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.articles.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                <i class="fa-solid fa-check text-[11px]"></i>
                <span>{{ $article->exists ? 'Simpan Perubahan' : 'Simpan Artikel' }}</span>
            </button>
        </div>
    </form>
</div>
@endsection
