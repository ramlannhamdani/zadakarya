@extends('layouts.admin')

@section('title', $article->exists ? 'Edit Artikel' : 'Artikel Baru')

@section('content')
<form method="POST"
      action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
      enctype="multipart/form-data" class="max-w-3xl"
      x-data="slugger(@js(old('title', $article->title)), @js(old('slug', $article->slug)), @js($article->exists))">
    @csrf
    @if($article->exists) @method('PUT') @endif

    <div class="admin-card">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="form-label" for="title">Judul <span class="text-brand-600">*</span></label>
                <input class="form-input" type="text" id="title" name="title" x-model="title" @input="syncSlug()" required>
            </div>
            <div>
                <label class="form-label" for="slug">Slug (URL)</label>
                <input class="form-input" type="text" id="slug" name="slug" x-model="slug" @input="touchSlug()" placeholder="otomatis dari judul">
            </div>
            <div>
                <label class="form-label" for="article_category_id">Kategori</label>
                <select class="form-input" id="article_category_id" name="article_category_id">
                    <option value="">—</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('article_category_id', $article->article_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="excerpt">Excerpt (ringkasan singkat)</label>
                <textarea class="form-input" id="excerpt" name="excerpt" rows="2" maxlength="500">{{ old('excerpt', $article->excerpt) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="content">Konten <span class="text-brand-600">*</span></label>
                <textarea class="form-input font-mono text-xs" id="content" name="content" rows="16" required
                          placeholder="Tulis konten artikel. HTML sederhana diperbolehkan: <h2>, <p>, <ul>, <ol>, <strong>, <a>...">{{ old('content', $article->content) }}</textarea>
                <p class="mt-1 text-xs text-neutral-500">Gunakan tag HTML sederhana untuk format: <code class="rounded bg-cream px-1">&lt;h2&gt;Judul&lt;/h2&gt;</code> <code class="rounded bg-cream px-1">&lt;p&gt;Paragraf&lt;/p&gt;</code> <code class="rounded bg-cream px-1">&lt;ul&gt;&lt;li&gt;Poin&lt;/li&gt;&lt;/ul&gt;</code></p>
            </div>
            <div>
                <label class="form-label" for="tags_text">Tags (pisahkan dengan koma)</label>
                <input class="form-input" type="text" id="tags_text" name="tags_text" value="{{ old('tags_text', implode(', ', $article->tags ?? [])) }}">
            </div>
            <div>
                <label class="form-label" for="featured_image">Featured Image</label>
                <x-admin.media-picker name="featured_image" :current="$article->featured_image">
                    <img src="{{ asset('storage/'.$article->featured_image) }}" alt="" class="h-20 w-auto rounded-lg object-cover">
                </x-admin.media-picker>
            </div>
        </div>
    </div>

    <div class="admin-card mt-5">
        <h2 class="font-extrabold text-ink">SEO &amp; Publikasi</h2>
        <div class="mt-4 grid gap-5">
            <div>
                <label class="form-label" for="seo_title">SEO Title</label>
                <input class="form-input" type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $article->seo_title) }}">
            </div>
            <div>
                <label class="form-label" for="seo_description">SEO Description</label>
                <textarea class="form-input" id="seo_description" name="seo_description" rows="2">{{ old('seo_description', $article->seo_description) }}</textarea>
            </div>
        </div>
        <div class="mt-5 border-t border-line pt-5" x-data="{
            pubStatus: '{{ old('status_action', $article->published_at ? ($article->published_at->isFuture() ? 'schedule' : 'publish_now') : 'draft') }}'
        }">
            <label class="form-label font-bold text-ink">Status Publikasi</label>
            <div class="mt-2 grid gap-3 sm:grid-cols-3">
                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border p-3 text-sm transition"
                       :class="pubStatus === 'publish_now' ? 'border-green-600 bg-green-50/50 font-bold text-green-900' : 'border-line hover:bg-cream'">
                    <input type="radio" name="status_action" value="publish_now" x-model="pubStatus" class="text-green-600 focus:ring-green-600">
                    <span>Terbitkan Sekarang</span>
                </label>

                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border p-3 text-sm transition"
                       :class="pubStatus === 'schedule' ? 'border-amber-600 bg-amber-50/50 font-bold text-amber-900' : 'border-line hover:bg-cream'">
                    <input type="radio" name="status_action" value="schedule" x-model="pubStatus" class="text-amber-600 focus:ring-amber-600">
                    <span>Jadwalkan Terbit</span>
                </label>

                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border p-3 text-sm transition"
                       :class="pubStatus === 'draft' ? 'border-neutral-600 bg-neutral-100 font-bold text-neutral-900' : 'border-line hover:bg-cream'">
                    <input type="radio" name="status_action" value="draft" x-model="pubStatus" class="text-neutral-600 focus:ring-neutral-600">
                    <span>Simpan Draft</span>
                </label>
            </div>

            <div class="mt-4" x-show="pubStatus === 'schedule'" x-cloak>
                <label class="form-label" for="published_at">Waktu Terbit Terjadwal (WIB)</label>
                <input class="form-input !w-auto" type="datetime-local" id="published_at" name="published_at"
                       value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}">
                <p class="mt-1 text-xs text-neutral-500">Artikel akan otomatis terbit dan dapat diakses publik pada tanggal dan jam ini.</p>
            </div>

            <div class="mt-5">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $article->is_featured)) class="rounded border-line text-brand-600 focus:ring-brand-600">
                    Artikel Unggulan (Tampil prioritas)
                </label>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn-primary">{{ $article->exists ? 'Simpan Perubahan' : 'Simpan Artikel' }}</button>
        <a href="{{ route('admin.articles.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
@endsection
