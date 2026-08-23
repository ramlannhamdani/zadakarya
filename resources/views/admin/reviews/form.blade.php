@extends('layouts.admin')

@section('title', $review->exists ? 'Edit Ulasan' : 'Tambah Ulasan')

@section('content')
<form method="POST" action="{{ $review->exists ? route('admin.reviews.update', $review) : route('admin.reviews.store') }}" class="admin-card max-w-2xl">
    @csrf
    @if($review->exists) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="form-label" for="author_name">Nama Pengulas <span class="text-brand-600">*</span></label>
            <input class="form-input" type="text" id="author_name" name="author_name" value="{{ old('author_name', $review->author_name) }}" required>
        </div>
        <div>
            <label class="form-label" for="rating">Rating <span class="text-brand-600">*</span></label>
            <select class="form-input" id="rating" name="rating">
                @foreach([5, 4, 3, 2, 1] as $r)
                    <option value="{{ $r }}" @selected(old('rating', $review->rating ?? 5) == $r)>{{ $r }} — {{ str_repeat('★', $r) }}{{ str_repeat('☆', 5 - $r) }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="form-label" for="content">Isi Ulasan <span class="text-brand-600">*</span></label>
            <textarea class="form-input" id="content" name="content" rows="5" required placeholder="Salin teks ulasan dari Google Maps...">{{ old('content', $review->content) }}</textarea>
        </div>
        <div>
            <label class="form-label" for="review_date">Tanggal Ulasan</label>
            <input class="form-input" type="date" id="review_date" name="review_date" value="{{ old('review_date', $review->review_date?->toDateString()) }}">
        </div>
        <div>
            <label class="form-label" for="sort_order">Urutan Tampil</label>
            <input class="form-input" type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $review->sort_order ?? 0) }}">
        </div>
    </div>

    <label class="mt-5 flex items-center gap-2 text-sm font-medium">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $review->is_published ?? true)) class="rounded border-line text-brand-600 focus:ring-brand-600">
        Tampilkan di website
    </label>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn-primary">{{ $review->exists ? 'Simpan Perubahan' : 'Tambah Ulasan' }}</button>
        <a href="{{ route('admin.reviews.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
@endsection
