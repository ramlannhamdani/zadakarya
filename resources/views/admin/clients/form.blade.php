@extends('layouts.admin')

@section('title', $client->exists ? 'Edit Klien' : 'Tambah Klien')

@section('content')
<form method="POST" action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}" enctype="multipart/form-data" class="admin-card max-w-2xl">
    @csrf
    @if($client->exists) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="form-label" for="name">Nama Klien <span class="text-brand-600">*</span></label>
            <input class="form-input" type="text" id="name" name="name" value="{{ old('name', $client->name) }}" required>
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Logo @unless($client->exists)<span class="text-brand-600">*</span>@endunless</label>
            @if($client->logo_path)
                <span class="mb-2 inline-flex h-20 items-center rounded-lg bg-cream px-4">
                    <img src="{{ asset('storage/'.$client->logo_path) }}" alt="{{ $client->name }}" class="max-h-14 w-auto object-contain">
                </span>
            @endif
            <x-admin.media-picker name="logo" />
            <p class="mt-1 text-xs text-neutral-500">Disarankan PNG/WebP dengan latar transparan, lebar minimal 300px. Tampil setinggi ±40px dalam mode abu-abu, berwarna saat hover.</p>
        </div>
        <div>
            <label class="form-label" for="website_url">Website (opsional)</label>
            <input class="form-input" type="url" id="website_url" name="website_url" value="{{ old('website_url', $client->website_url) }}" placeholder="https://">
        </div>
        <div>
            <label class="form-label" for="sort_order">Urutan Tampil</label>
            <input class="form-input" type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $client->sort_order ?? 0) }}">
        </div>
    </div>

    <label class="mt-5 flex items-center gap-2 text-sm font-medium">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active ?? true)) class="rounded border-line text-brand-600 focus:ring-brand-600">
        Tampilkan di website
    </label>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" class="btn-primary">{{ $client->exists ? 'Simpan Perubahan' : 'Tambah Klien' }}</button>
        <a href="{{ route('admin.clients.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
@endsection
