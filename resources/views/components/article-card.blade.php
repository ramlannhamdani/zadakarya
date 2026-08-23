@props(['article'])
<a href="{{ route('blog.show', $article) }}" class="group flex flex-col overflow-hidden rounded-xl border border-line bg-white transition hover:border-brand-600/40 hover:shadow-sm">
    @if($article->featured_image)
        <img src="{{ asset('storage/'.$article->featured_image) }}" alt="{{ $article->title }}" loading="lazy" class="aspect-[16/9] w-full object-cover">
    @else
        <x-placeholder-image :label="$article->category?->name ?? 'Artikel'" class="aspect-[16/9] w-full" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2 text-xs text-neutral-500">
            @if($article->category)<span class="font-semibold uppercase tracking-wide text-warm-600">{{ $article->category->name }}</span><span>&bull;</span>@endif
            <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
        </div>
        <h3 class="mt-2 font-bold leading-snug text-ink group-hover:text-brand-600">{{ $article->title }}</h3>
        <p class="mt-2 line-clamp-2 flex-1 text-sm text-neutral-600">{{ $article->excerpt }}</p>
        <span class="mt-3 text-sm font-semibold text-brand-600">Baca Artikel &rarr;</span>
    </div>
</a>
