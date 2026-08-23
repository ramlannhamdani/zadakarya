@extends('layouts.admin')

@section('title', 'Layanan')

@section('content')
<div class="flex justify-end">
    <a href="{{ route('admin.services.create') }}" class="btn-primary">+ Layanan Baru</a>
</div>

<div class="admin-card mt-5 overflow-x-auto !p-0">
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
