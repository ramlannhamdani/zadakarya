@props(['portfolio'])

@php
    // Ikon dipilih dari nama kategori; jatuh ke ikon kaos bila tidak cocok.
    $icons = [
        'kantor' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 6.006a2.18 2.18 0 0 1-.75.412m0 0a48.9 48.9 0 0 1-15 0m15 0a2.18 2.18 0 0 0 .75-.412M3.75 14.15a2.18 2.18 0 0 1-.75-1.661V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m0 0V5.25A2.25 2.25 0 0 1 10.5 3h3a2.25 2.25 0 0 1 2.25 2.25v.894m-7.5 0a48.667 48.667 0 0 1 7.5 0M12 12.75h.008v.008H12v-.008z',
        'sekolah' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
        'olahraga' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
        'celana' => 'M8 3h8l.6 8.5.9 9.5h-4l-1.5-9-1.5 9h-4l.9-9.5L8 3z',
        'jaket' => 'M9 3 4.5 5.5 6 11l2-1v10.5h8V10l2 1 1.5-5.5L15 3l-3 2-3-2zm3 2v15.5',
        'hoodie' => 'M9 3 4.5 5.5 6 11l2-1v10.5h8V10l2 1 1.5-5.5L15 3l-3 2-3-2zm3 2v15.5',
        'custom' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
    ];
    $shirt = 'M8 3.5 4 6l1.5 4L8 9v11.5h8V9l2.5 1L20 6l-4-2.5C15 5 13.5 5.5 12 5.5S9 5 8 3.5z';

    $categoryName = \Illuminate\Support\Str::lower($portfolio->category?->name ?? '');
    $icon = $shirt;
    foreach ($icons as $keyword => $path) {
        if (str_contains($categoryName, $keyword)) {
            $icon = $path;
            break;
        }
    }
@endphp

<a href="{{ route('portfolio.show', $portfolio) }}"
   class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-[0_10px_30px_-18px_rgba(32,32,32,.35)] ring-1 ring-black/[0.04] transition hover:shadow-[0_18px_40px_-20px_rgba(108,16,5,.45)]">
    @if($portfolio->cover_image)
        <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="{{ $portfolio->title }}" loading="lazy"
             class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
    @else
        <x-placeholder-image :label="$portfolio->category?->name ?? 'Portfolio'" class="aspect-[4/3] w-full" />
    @endif

    <div class="flex flex-1 items-start gap-3 p-4 sm:gap-3.5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white">
            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
            </svg>
        </span>

        {{-- line-clamp memakai display:-webkit-box, jadi jangan digabung dengan kelas `block` --}}
        <span class="min-w-0 flex-1">
            <span class="line-clamp-2 text-[15px] font-bold leading-snug text-ink group-hover:text-brand-600">{{ $portfolio->title }}</span>
            @if($portfolio->description)
                <span class="mt-1 line-clamp-2 text-[12.5px] leading-snug text-neutral-500">{{ strip_tags($portfolio->description) }}</span>
            @elseif($portfolio->category)
                <span class="mt-1 line-clamp-2 text-[12.5px] leading-snug text-neutral-500">{{ $portfolio->category->name }}@if($portfolio->production_year) &bull; {{ $portfolio->production_year }}@endif</span>
            @endif
        </span>

        <svg class="mt-2.5 h-4 w-4 shrink-0 text-brand-600 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3"/>
        </svg>
    </div>
</a>
