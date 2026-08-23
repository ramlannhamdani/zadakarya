@props(['portfolio'])
<a href="{{ route('portfolio.show', $portfolio) }}" class="group overflow-hidden rounded-xl border border-line bg-white transition hover:border-brand-600/40 hover:shadow-sm">
    <div class="relative">
        @if($portfolio->cover_image)
            <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="{{ $portfolio->title }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]">
        @else
            <x-placeholder-image :label="$portfolio->category?->name ?? 'Portfolio'" class="aspect-[4/3] w-full" />
        @endif
        @if($portfolio->is_featured)
            <span class="absolute left-3 top-3 rounded-full bg-brand-600 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Featured</span>
        @endif
    </div>
    <div class="p-5">
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-warm-600">
            @if($portfolio->category){{ $portfolio->category->name }}@endif
            @if($portfolio->production_year)<span class="text-neutral-400">&bull; {{ $portfolio->production_year }}</span>@endif
        </div>
        <h3 class="mt-1.5 font-bold text-ink group-hover:text-brand-600">{{ $portfolio->title }}</h3>
    </div>
</a>
