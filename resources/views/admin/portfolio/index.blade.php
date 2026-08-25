@extends('layouts.admin')

@section('title', 'Portfolio')

@section('content')
<div class="grid gap-5 lg:grid-cols-4">
    <div class="lg:col-span-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" class="flex min-w-0 flex-1 gap-2 sm:flex-none">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul..." class="form-input min-w-0 flex-1 sm:!w-56">
                <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
            </form>
            <a href="{{ route('admin.portfolio.create') }}" class="btn-primary">+ Portfolio Baru</a>
        </div>

        {{-- Mobile: kartu (tabel di md+) --}}
<div class="mt-5 space-y-3 md:hidden">
    @forelse($portfolios as $portfolio)
        <div class="admin-card !p-4">
            <div class="flex gap-3">
                @if($portfolio->cover_image)
                    <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                @else
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-cream text-lg font-bold text-warm-600">{{ mb_substr($portfolio->title, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-semibold leading-snug text-ink">{{ $portfolio->title }}</p>
                    <p class="mt-1 text-xs text-neutral-500">{{ $portfolio->category?->name ?? 'Tanpa kategori' }} &bull; {{ $portfolio->production_year ?? '—' }}</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $portfolio->is_published ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $portfolio->is_published ? 'Publish' : 'Draft' }}</span>
                        @if($portfolio->is_featured)<span class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-bold text-brand-600">Featured</span>@endif
                    </div>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-4 border-t border-line pt-3 text-xs font-semibold">
                <a href="{{ route('portfolio.show', $portfolio) }}" target="_blank" class="text-neutral-500">Lihat</a>
                <a href="{{ route('admin.portfolio.edit', $portfolio) }}" class="text-brand-600">Edit</a>
                <form method="POST" action="{{ route('admin.portfolio.destroy', $portfolio) }}" onsubmit="return confirm('Hapus portfolio ini beserta semua gambarnya?')" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-500">Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <div class="admin-card py-8 text-center text-neutral-500">Belum ada portfolio.</div>
    @endforelse
</div>
<div class="admin-card mt-5 hidden overflow-x-auto !p-0 md:block">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                        <th class="px-5 py-3">Judul</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3">Tahun</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($portfolios as $portfolio)
                        <tr class="hover:bg-cream/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if($portfolio->cover_image)
                                        <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                                    @else
                                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cream text-xs font-bold text-warm-600">{{ mb_substr($portfolio->title, 0, 1) }}</span>
                                    @endif
                                    <div>
                                        <span class="font-semibold text-ink">{{ $portfolio->title }}</span>
                                        @if($portfolio->is_featured)<span class="ml-1.5 rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-bold text-brand-600">Featured</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-neutral-600">{{ $portfolio->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-neutral-600">{{ $portfolio->production_year ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $portfolio->is_published ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">
                                    {{ $portfolio->is_published ? 'Publish' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('portfolio.show', $portfolio) }}" target="_blank" class="text-xs font-semibold text-neutral-500 hover:text-brand-600">Lihat</a>
                                    <a href="{{ route('admin.portfolio.edit', $portfolio) }}" class="text-xs font-semibold text-brand-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.portfolio.destroy', $portfolio) }}" onsubmit="return confirm('Hapus portfolio ini beserta semua gambarnya?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-neutral-500">Belum ada portfolio.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $portfolios->links() }}</div>
    </div>

    <div class="admin-card h-fit">
        <h2 class="font-extrabold text-ink">Kategori</h2>
        <form method="POST" action="{{ route('admin.portfolio-categories.store') }}" class="mt-3 flex gap-2">
            @csrf
            <input class="form-input" type="text" name="name" placeholder="Kategori baru..." required>
            <button type="submit" class="btn-primary !px-4">+</button>
        </form>
        <ul class="mt-4 divide-y divide-line text-sm">
            @foreach($categories as $category)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $category->name }} <span class="text-xs text-neutral-400">({{ $category->portfolios_count }})</span></span>
                    <form method="POST" action="{{ route('admin.portfolio-categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Hapus</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
