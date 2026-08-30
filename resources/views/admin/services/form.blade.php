@extends('layouts.admin')

@section('title', $service->exists ? 'Edit Layanan' : 'Layanan Baru')

@section('content')
<form method="POST"
      action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}"
      enctype="multipart/form-data" class="max-w-3xl"
      x-data="slugger(@js(old('name', $service->name)), @js(old('slug', $service->slug)), @js($service->exists))">
    @csrf
    @if($service->exists) @method('PUT') @endif

    <div class="admin-card">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="name">Nama Layanan <span class="text-brand-600">*</span></label>
                <input class="form-input" type="text" id="name" name="name" x-model="title" @input="syncSlug()" required>
            </div>
            <div>
                <label class="form-label" for="slug">Slug (URL)</label>
                <input class="form-input" type="text" id="slug" name="slug" x-model="slug" @input="touchSlug()" placeholder="otomatis dari nama">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="short_description">Deskripsi Singkat</label>
                <textarea class="form-input" id="short_description" name="short_description" rows="2" maxlength="500">{{ old('short_description', $service->short_description) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="description">Deskripsi Lengkap (HTML diperbolehkan)</label>
                <textarea class="form-input font-mono text-xs" id="description" name="description" rows="8">{{ old('description', $service->description) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="features_text">Keunggulan / Fitur (satu per baris)</label>
                <textarea class="form-input" id="features_text" name="features_text" rows="4" placeholder="Bahan berkualitas&#10;Bordir logo">{{ old('features_text', implode("\n", $service->features ?? [])) }}</textarea>
            </div>
            <div>
                <label class="form-label" for="material_info">Informasi Bahan</label>
                <textarea class="form-input" id="material_info" name="material_info" rows="3">{{ old('material_info', $service->material_info) }}</textarea>
            </div>
            <div>
                <label class="form-label" for="production_info">Informasi Produksi</label>
                <textarea class="form-input" id="production_info" name="production_info" rows="3">{{ old('production_info', $service->production_info) }}</textarea>
            </div>
            <div>
                <label class="form-label" for="min_order">Minimum Order</label>
                <input class="form-input" type="text" id="min_order" name="min_order" value="{{ old('min_order', $service->min_order) }}" placeholder="Contoh: 24 pcs">
            </div>
            <div>
                <label class="form-label" for="sort_order">Urutan Tampil</label>
                <input class="form-input" type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label" for="featured_image">Foto Utama</label>
                <x-admin.media-picker name="featured_image" :current="$service->featured_image">
                    <img src="{{ asset('storage/'.$service->featured_image) }}" alt="" class="h-24 w-auto rounded-lg object-cover">
                </x-admin.media-picker>
            </div>
        </div>
    </div>

    <div class="admin-card mt-5">
        <h2 class="font-extrabold text-ink">SEO</h2>
        <div class="mt-4 grid gap-5">
            <div>
                <label class="form-label" for="seo_title">SEO Title</label>
                <input class="form-input" type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $service->seo_title) }}">
            </div>
            <div>
                <label class="form-label" for="seo_description">SEO Description</label>
                <textarea class="form-input" id="seo_description" name="seo_description" rows="2">{{ old('seo_description', $service->seo_description) }}</textarea>
            </div>
        </div>
        <label class="mt-5 flex items-center gap-2 text-sm font-medium">
            <input type="hidden" name="is_published" value="0">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $service->is_published ?? true)) class="rounded border-line text-brand-600 focus:ring-brand-600">
            Publikasikan layanan ini
        </label>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn-primary">{{ $service->exists ? 'Simpan Perubahan' : 'Buat Layanan' }}</button>
        <a href="{{ route('admin.services.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
@endsection
