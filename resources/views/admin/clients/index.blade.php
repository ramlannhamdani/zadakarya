@extends('layouts.admin')

@section('title', 'Klien Kami')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-neutral-500">Logo klien tampil sebagai carousel "Dipercaya oleh" di bawah hero homepage. Gunakan PNG/WebP transparan agar rapi.</p>
    <a href="{{ route('admin.clients.create') }}" class="btn-primary">+ Tambah Klien</a>
</div>

@if($clients->isEmpty())
    <div class="admin-card mt-5 py-12 text-center text-neutral-500">Belum ada klien. Tambahkan logo klien pertama Anda.</div>
@else
    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($clients as $client)
            <div class="admin-card !p-4 {{ $client->is_active ? '' : 'opacity-60' }}">
                <div class="flex h-24 items-center justify-center rounded-lg bg-cream p-4">
                    <img src="{{ asset('storage/'.$client->logo_path) }}" alt="{{ $client->name }}" class="max-h-16 w-auto max-w-full object-contain">
                </div>
                <div class="mt-3 flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink">{{ $client->name }}</p>
                        @if($client->website_url)
                            <a href="{{ $client->website_url }}" target="_blank" rel="noopener" class="block truncate text-xs text-neutral-500 hover:text-brand-600">{{ $client->website_url }}</a>
                        @endif
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $client->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="mt-3 flex items-center gap-4 border-t border-line pt-3 text-xs font-semibold">
                    <span class="text-neutral-400">Urutan {{ $client->sort_order }}</span>
                    <a href="{{ route('admin.clients.edit', $client) }}" class="ml-auto text-brand-600">Edit</a>
                    <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" onsubmit="return confirm('Hapus klien {{ $client->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500">Hapus</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
