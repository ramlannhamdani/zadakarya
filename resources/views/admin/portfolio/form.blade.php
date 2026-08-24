@extends('layouts.admin')

@section('title', $portfolio->exists ? 'Edit Portfolio' : 'Portfolio Baru')

@section('content')
<form method="POST"
      action="{{ $portfolio->exists ? route('admin.portfolio.update', $portfolio) : route('admin.portfolio.store') }}"
      enctype="multipart/form-data" class="max-w-3xl"
      x-data="slugger(@js(old('title', $portfolio->title)), @js(old('slug', $portfolio->slug)), @js($portfolio->exists))">
    @csrf
    @if($portfolio->exists) @method('PUT') @endif

    <div class="admin-card">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="title">Judul <span class="text-brand-600">*</span></label>
                <input class="form-input" type="text" id="title" name="title" x-model="title" @input="syncSlug()" required>
            </div>
            <div>
                <label class="form-label" for="slug">Slug (URL)</label>
                <input class="form-input" type="text" id="slug" name="slug" x-model="slug" @input="touchSlug()" placeholder="otomatis dari judul">
            </div>
            <div>
                <label class="form-label" for="portfolio_category_id">Kategori</label>
                <select class="form-input" id="portfolio_category_id" name="portfolio_category_id">
                    <option value="">—</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('portfolio_category_id', $portfolio->portfolio_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="production_year">Tahun Produksi</label>
                <input class="form-input" type="text" id="production_year" name="production_year" value="{{ old('production_year', $portfolio->production_year) }}" placeholder="{{ date('Y') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="description">Deskripsi</label>
                <textarea class="form-input" id="description" name="description" rows="4">{{ old('description', $portfolio->description) }}</textarea>
            </div>
            <div>
                <label class="form-label" for="client_name">Nama Klien (opsional)</label>
                <input class="form-input" type="text" id="client_name" name="client_name" value="{{ old('client_name', $portfolio->client_name) }}">
            </div>
            <div>
                <label class="form-label" for="tags_text">Tags (pisahkan dengan koma)</label>
                <input class="form-input" type="text" id="tags_text" name="tags_text" value="{{ old('tags_text', implode(', ', $portfolio->tags ?? [])) }}" placeholder="seragam, drill, bordir">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="cover_image">Cover Image</label>
                @if($portfolio->cover_image)
                    <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="" class="mb-2 h-24 rounded-lg object-cover">
                @endif
                <input class="form-input !py-2" type="file" id="cover_image" name="cover_image" accept="image/*">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="gallery">Tambah Foto Galeri (bisa lebih dari satu)</label>
                <input class="form-input !py-2" type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
                <p class="mt-1 text-xs text-neutral-500">Thumbnail dibuat otomatis.</p>
            </div>
        </div>

        @if($portfolio->exists && $portfolio->images->isNotEmpty())
            <div class="mt-5 border-t border-line pt-5">
                <p class="form-label">Galeri Saat Ini</p>
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                    @foreach($portfolio->images as $image)
                        <div class="relative">
                            <img src="{{ asset('storage/'.($image->thumb_path ?: $image->image_path)) }}" alt="" class="aspect-square w-full rounded-lg object-cover">
                            <button type="submit"
                                    form="delete-image-{{ $image->id }}"
                                    onclick="return confirm('Hapus gambar ini?')"
                                    class="absolute right-1.5 top-1.5 rounded-full bg-white/90 p-1 text-red-500 shadow hover:bg-white">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="admin-card mt-5">
        <h2 class="font-extrabold text-ink">SEO &amp; Publikasi</h2>
        <div class="mt-4 grid gap-5">
            <div>
                <label class="form-label" for="seo_title">SEO Title</label>
                <input class="form-input" type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $portfolio->seo_title) }}">
            </div>
            <div>
                <label class="form-label" for="seo_description">SEO Description</label>
                <textarea class="form-input" id="seo_description" name="seo_description" rows="2">{{ old('seo_description', $portfolio->seo_description) }}</textarea>
            </div>
        </div>
        <div class="mt-5 flex flex-wrap gap-6">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $portfolio->is_published ?? true)) class="rounded border-line text-brand-600 focus:ring-brand-600">
                Publikasikan
            </label>
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $portfolio->is_featured)) class="rounded border-line text-brand-600 focus:ring-brand-600">
                Jadikan Featured (tampil di homepage)
            </label>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn-primary">{{ $portfolio->exists ? 'Simpan Perubahan' : 'Buat Portfolio' }}</button>
        <a href="{{ route('admin.portfolio.index') }}" class="btn-outline">Batal</a>
    </div>
</form>

@if($portfolio->exists)
    @foreach($portfolio->images as $image)
        <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('admin.portfolio.images.destroy', $image) }}">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif
@endsection
