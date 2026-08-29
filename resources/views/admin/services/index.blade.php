@extends('layouts.admin')

@section('title', 'Layanan')

@section('content')
<div class="flex justify-end">
    <a href="{{ route('admin.services.create') }}" class="btn-primary">+ Layanan Baru</a>
</div>

{{-- Mobile: kartu (tabel di md+) --}}
<div class="mt-5 grid gap-3 md:grid-cols-2 lg:hidden">
    @forelse($services as $service)
        <div class="admin-card !p-4">
            <div class="flex gap-3">
                @if($service->featured_image)
                    <img src="{{ asset('storage/'.$service->featured_image) }}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                @else
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-cream text-lg font-bold text-warm-600">{{ mb_substr($service->name, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-semibold leading-snug text-ink">{{ $service->name }}</p>
                    <p class="mt-1 text-xs text-neutral-500">Min. order {{ $service->min_order ?? '—' }} &bull; urutan {{ $service->sort_order }}</p>
                    <span class="mt-1.5 inline-block rounded-full px-2 py-0.5 text-[11px] font-bold {{ $service->is_published ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $service->is_published ? 'Publish' : 'Draft' }}</span>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-4 border-t border-line pt-3 text-xs font-semibold">
                <a href="{{ route('services.show', $service) }}" target="_blank" class="text-neutral-500">Lihat</a>
                <a href="{{ route('admin.services.edit', $service) }}" class="text-brand-600">Edit</a>
                <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('Hapus layanan {{ $service->name }}?')" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-500">Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <div class="admin-card py-8 text-center text-neutral-500 md:col-span-2">Belum ada layanan.</div>
    @endforelse
</div>
<div class="admin-card mt-5 hidden overflow-x-auto !p-0 lg:block">
    <table class="w-full min-w-[680px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">Layanan</th>
                <th class="px-5 py-3">Slug</th>
                <th class="px-5 py-3">Min. Order</th>
                <th class="px-5 py-3 text-center">Urutan</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($services as $service)
                <tr class="hover:bg-cream/40">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            @if($service->featured_image)
                                <img src="{{ asset('storage/'.$service->featured_image) }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cream text-xs font-bold text-warm-600">{{ mb_substr($service->name, 0, 1) }}</span>
                            @endif
                            <span class="font-semibold text-ink">{{ $service->name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3.5 font-mono text-xs text-neutral-500">/layanan/{{ $service->slug }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $service->min_order ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-center">{{ $service->sort_order }}</td>
                    <td class="px-5 py-3.5">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $service->is_published ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">
                            {{ $service->is_published ? 'Publish' : 'Draft' }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('services.show', $service) }}" target="_blank" class="text-xs font-semibold text-neutral-500 hover:text-brand-600">Lihat</a>
                            <a href="{{ route('admin.services.edit', $service) }}" class="text-xs font-semibold text-brand-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('Hapus layanan {{ $service->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-500">Belum ada layanan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $services->links() }}</div>
@endsection
