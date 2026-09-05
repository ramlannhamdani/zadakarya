@props(['article', 'class' => 'aspect-[16/9] w-full', 'showScallop' => true])

@if($article->featured_image)
    <img src="{{ asset('storage/'.$article->featured_image) }}" alt="{{ $article->title }}" loading="lazy" class="{{ $class }} object-cover">
@else
    @php
        $catUpper = strtoupper($article->category?->name ?? 'KONVEKSI');
        
        // Warna pill kategori sesuai jenis kategori (persis referensi Image 2)
        [$badgeBg, $badgeColor] = match (true) {
            str_contains(strtolower($catUpper), 'panduan') => ['#c0ddd5', '#113a30'],
            str_contains(strtolower($catUpper), 'produksi') => ['#bce2e6', '#093a42'],
            str_contains(strtolower($catUpper), 'tips') => ['#f9e79f', '#4e3804'],
            str_contains(strtolower($catUpper), 'bahan') => ['#e3d7f2', '#391a61'],
            default => ['#f9e79f', '#4e3804'],
        };
        
        $num = sprintf('%02d', ($article->id ?? 1) % 100);
        $patId = 'scallop-' . ($article->id ?? 'def') . '-' . substr(md5($article->title), 0, 4);
    @endphp
    <div {{ $attributes->merge(['class' => 'relative flex flex-col justify-between overflow-hidden p-5 select-none ' . $class]) }}
         style="background-color: #14332a; background: linear-gradient(135deg, #173b30 0%, #122d25 55%, #0d201a 100%); color: #ffffff;">
        
        {{-- Aksen lingkaran geometris di sudut kanan atas (persis Image 2) --}}
        <div class="pointer-events-none absolute -top-8 -right-8 h-36 w-36 rounded-full"
             style="background-color: rgba(9, 23, 19, 0.65);"></div>
        <div class="pointer-events-none absolute top-4 right-10 h-24 w-24 rounded-full"
             style="background-color: rgba(255, 255, 255, 0.03); filter: blur(4px);"></div>

        {{-- Atas: Pill Kategori --}}
        <div class="relative z-10 flex items-center justify-between">
            <span class="inline-block px-2.5 py-0.5 font-extrabold uppercase tracking-wider"
                  style="background-color: {{ $badgeBg }}; color: {{ $badgeColor }}; font-size: 10px; border-radius: 4px; letter-spacing: 0.12em;">
                {{ $catUpper }}
            </span>
        </div>

        {{-- Tengah: Judul Artikel --}}
        <div class="relative z-10 my-auto py-2">
            <h4 class="font-bold leading-snug line-clamp-3"
                style="color: #ffffff; font-size: 1.05rem; font-weight: 700; line-height: 1.35; letter-spacing: -0.01em;">
                {{ $article->title }}
            </h4>
        </div>

        {{-- Bawah: Brand Watermark & Angka Besar --}}
        <div class="relative z-10 flex items-end justify-between pt-2.5"
             style="border-top: 1px solid rgba(255, 255, 255, 0.12);">
            <span style="font-size: 11px; color: rgba(255, 255, 255, 0.65); font-weight: 500; letter-spacing: 0.02em;">
                Zada Karya <span style="color: rgba(255, 255, 255, 0.4);">catat kebutuhan seragam</span>
            </span>
            <span style="font-size: 2.1rem; font-weight: 900; line-height: 1; color: rgba(124, 169, 156, 0.4); letter-spacing: -0.05em;">
                {{ $num }}
            </span>
        </div>

        {{-- Aksen Gerigi / Scalloped Wave di perbatasan bawah kartu (persis Image 2) --}}
        @if($showScallop)
            <div class="absolute inset-x-0 bottom-0 pointer-events-none" style="height: 8px; line-height: 0; overflow: hidden; z-index: 10;">
                <svg viewBox="0 0 400 8" preserveAspectRatio="none" style="width: 100%; height: 100%; display: block; fill: #ffffff;">
                    <defs>
                        <pattern id="{{ $patId }}" width="16" height="8" patternUnits="userSpaceOnUse">
                            <path d="M 0 0 C 4 7, 12 7, 16 0 L 16 8 L 0 8 Z" fill="#ffffff"></path>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#{{ $patId }})"></rect>
                </svg>
            </div>
        @endif
    </div>
@endif
