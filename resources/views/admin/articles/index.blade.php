@extends('layouts.admin')

@section('title', 'Blog / Artikel')

@section('content')
<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_15rem] xl:grid-cols-4">
    <div class="min-w-0 xl:col-span-3">
        {{-- Tab filter status --}}
        <div class="mb-4 flex flex-wrap items-center gap-2 border-b border-line pb-3">
            <a href="{{ route('admin.articles.index', array_merge(request()->except('status', 'page'), [])) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ !$currentStatus ? 'bg-brand-600 text-white' : 'bg-cream text-neutral-600 hover:bg-neutral-200' }}">
                Semua ({{ $statusCounts['all'] ?? 0 }})
            </a>
            <a href="{{ route('admin.articles.index', array_merge(request()->except('page'), ['status' => 'published'])) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentStatus === 'published' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200' }}">
                Terbit ({{ $statusCounts['published'] ?? 0 }})
            </a>
            <a href="{{ route('admin.articles.index', array_merge(request()->except('page'), ['status' => 'scheduled'])) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentStatus === 'scheduled' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                Dijadwalkan ({{ $statusCounts['scheduled'] ?? 0 }})
            </a>
            <a href="{{ route('admin.articles.index', array_merge(request()->except('page'), ['status' => 'draft'])) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentStatus === 'draft' ? 'bg-neutral-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 border border-neutral-200' }}">
                Draft ({{ $statusCounts['draft'] ?? 0 }})
            </a>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" class="flex min-w-0 flex-1 gap-2 sm:flex-none">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul..." class="form-input min-w-0 flex-1 sm:!w-56">
                <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
            </form>
            <a href="{{ route('admin.articles.create') }}" class="btn-primary">+ Artikel Baru</a>
        </div>

        {{-- Mobile: kartu (tabel di md+) --}}
<div class="mt-5 space-y-3 md:hidden">
    @forelse($articles as $article)
        <div class="admin-card !p-4">
            <div class="flex gap-3">
                @if($article->featured_image)
                    <img src="{{ asset('storage/'.$article->featured_image) }}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                @else
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-emerald-950 text-sm font-bold text-white/70">{{ mb_substr($article->title, 0, 2) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-semibold leading-snug text-ink">{{ $article->title }}</p>
                    <p class="mt-1 text-xs text-neutral-500">{{ $article->category?->name ?? 'Tanpa kategori' }} &bull; {{ $article->published_at?->format('d/m/Y H:i') ?? 'belum terbit' }}</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        @if($article->status === 'published')
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-bold text-green-700">Terbit</span>
                        @elseif($article->status === 'scheduled')
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">Dijadwalkan</span>
                        @else
                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-bold text-neutral-600">Draft</span>
                        @endif
                        @if($article->is_featured)<span class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-bold text-brand-600">Unggulan</span>@endif
                    </div>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-4 border-t border-line pt-3 text-xs font-semibold">
                <a href="{{ route('blog.show', $article) }}" target="_blank" class="text-neutral-500">Lihat</a>
                <a href="{{ route('admin.articles.edit', $article) }}" class="text-brand-600">Edit</a>
                <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Hapus artikel ini?')" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-500">Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <div class="admin-card py-8 text-center text-neutral-500">Belum ada artikel.</div>
    @endforelse
</div>
<div class="admin-card mt-5 hidden overflow-x-auto !p-0 md:block">
            <table class="w-full min-w-[600px] text-sm">
                <thead>
                    <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                        <th class="px-5 py-3">Judul</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3">Terbit</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($articles as $article)
                        <tr class="hover:bg-cream/40">
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-ink leading-snug">{{ $article->title }}</div>
                                @if($article->is_featured)
                                    <span class="mt-1 inline-block rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-600">Unggulan</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-neutral-600 whitespace-nowrap">{{ $article->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-neutral-600 whitespace-nowrap">
                                @if($article->published_at)
                                    <div class="font-medium">{{ $article->published_at->format('d/m/Y') }}</div>
                                    <div class="text-[11px] text-neutral-400">{{ $article->published_at->format('H:i') }} WIB</div>
                                @else
                                    <span class="text-neutral-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($article->status === 'published')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>
                                        Terbit
                                    </span>
                                @elseif($article->status === 'scheduled')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800" title="Terbit: {{ $article->published_at?->format('d/m/Y H:i') }} WIB">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                        Dijadwalkan
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-bold text-neutral-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-neutral-400"></span>
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('blog.show', $article) }}" target="_blank" class="text-xs font-semibold text-neutral-500 hover:text-brand-600">Lihat</a>
                                    <a href="{{ route('admin.articles.edit', $article) }}" class="text-xs font-semibold text-brand-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Hapus artikel ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-neutral-500">Belum ada artikel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $articles->links() }}</div>
    </div>

    <div class="admin-card h-fit">
        <h2 class="font-extrabold text-ink">Kategori</h2>
        <form method="POST" action="{{ route('admin.article-categories.store') }}" class="mt-3 flex gap-2">
            @csrf
            <input class="form-input" type="text" name="name" placeholder="Kategori baru..." required>
            <button type="submit" class="btn-primary !px-4">+</button>
        </form>
        <ul class="mt-4 divide-y divide-line text-sm">
            @foreach($categories as $category)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $category->name }} <span class="text-xs text-neutral-400">({{ $category->articles_count }})</span></span>
                    <form method="POST" action="{{ route('admin.article-categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Hapus</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
