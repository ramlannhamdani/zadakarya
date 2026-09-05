@props(['article', 'class' => 'aspect-[16/9] w-full', 'showScallop' => true])

@if($article->featured_image)
    <img src="{{ asset('storage/'.$article->featured_image) }}" alt="{{ $article->title }}" loading="lazy" class="{{ $class }} object-cover">
@else
    @php
        $catUpper = strtoupper($article->category?->name ?? 'KONVEKSI');
        $badgeClass = match (true) {
            str_contains(strtolower($catUpper), 'panduan') => 'bg-[#c2ded7] text-[#144237]',
            str_contains(strtolower($catUpper), 'produksi') => 'bg-[#bfe2e6] text-[#0c3f47]',
            str_contains(strtolower($catUpper), 'tips') => 'bg-[#fae8a4] text-[#523d06]',
            str_contains(strtolower($catUpper), 'bahan') => 'bg-[#e2d5f2] text-[#3c1b63]',
            default => 'bg-[#fae8a4] text-[#523d06]',
        };
        $num = sprintf('%02d', ($article->id ?? 1) % 100);
        $patId = 'scallop-' . ($article->id ?? 'def') . '-' . substr(md5($article->title), 0, 4);
    @endphp
    <div {{ $attributes->merge(['class' => 'relative flex flex-col justify-between overflow-hidden bg-gradient-to-br from-[#17382f] via-[#132f27] to-[#0f241e] p-5 text-white select-none ' . $class]) }}>
        {{-- Circular decorative shape on top right (matches screenshot) --}}
        <div class="pointer-events-none absolute -top-8 -right-8 h-36 w-36 rounded-full bg-[#0a1b16]/70"></div>
        <div class="pointer-events-none absolute top-4 right-8 h-20 w-20 rounded-full bg-white/[0.03] blur-sm"></div>

        {{-- Top: Category Pill --}}
        <div class="relative z-10 flex items-center justify-between">
            <span class="inline-block rounded px-2.5 py-0.5 text-[10px] sm:text-[11px] font-extrabold uppercase tracking-widest {{ $badgeClass }}">
                {{ $catUpper }}
            </span>
        </div>

        {{-- Center: Title --}}
        <div class="relative z-10 my-auto py-2">
            <h4 class="font-bold text-white text-base sm:text-lg lg:text-[1.1rem] leading-snug tracking-tight line-clamp-3">
                {{ $article->title }}
            </h4>
        </div>

        {{-- Bottom: Brand Watermark + Large Number --}}
        <div class="relative z-10 flex items-end justify-between border-t border-white/10 pt-2 text-xs">
            <span class="text-[10px] sm:text-[11px] font-medium text-white/60 tracking-wider">
                Zada Karya <span class="text-white/40">konveksi &amp; garment</span>
            </span>
            <span class="font-black text-2xl sm:text-3xl lg:text-4xl leading-none text-[#7ca99c]/35 tracking-tighter">
                {{ $num }}
            </span>
        </div>

        {{-- Scalloped wave pattern at bottom edge (matches screenshot) --}}
        @if($showScallop)
            <div class="absolute inset-x-0 bottom-0 h-2.5 overflow-hidden leading-none z-10 pointer-events-none">
                <svg class="w-full h-full text-white fill-current" viewBox="0 0 400 10" preserveAspectRatio="none">
                    <defs>
                        <pattern id="{{ $patId }}" width="16" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 0 0 C 4 9, 12 9, 16 0 L 16 10 L 0 10 Z" fill="currentColor"></path>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#{{ $patId }})"></rect>
                </svg>
            </div>
        @endif
    </div>
@endif
