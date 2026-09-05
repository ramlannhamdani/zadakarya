@props(['article', 'class' => 'aspect-[16/9] w-full', 'mini' => false, 'showScallop' => false])

@if($article->featured_image)
    <img src="{{ asset('storage/'.$article->featured_image) }}" alt="{{ $article->title }}" loading="lazy" class="{{ $class }} object-cover">
@else
    @php
        $catName = $article->category?->name ?? 'Konveksi & Garment';
        $catUpper = strtoupper($catName);
        $num = sprintf('%02d', ($article->id ?? 1) % 100);
        $isMini = $mini || str_contains($class, 'h-14') || str_contains($class, 'h-16') || str_contains($class, 'w-14') || str_contains($class, 'w-16');
        $patDotsId = 'zk-dots-' . ($article->id ?? 'def') . '-' . substr(md5($article->title), 0, 4);
        $gradBarId = 'zk-bar-' . ($article->id ?? 'def') . '-' . substr(md5($article->title), 0, 4);
    @endphp

    @if($isMini)
        {{-- Versi Mini / Thumbnail Kecil (Sidebar / Tabel Admin) --}}
        <div {{ $attributes->merge(['class' => 'relative flex flex-col items-center justify-center overflow-hidden select-none ' . $class]) }}
             style="background: linear-gradient(135deg, #420903 0%, #6c1005 50%, #2e0602 100%); color: #ffffff;">
            <svg class="h-6 w-6" style="color: #c8a276;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3.5 4 6l1.5 4L8 9v11.5h8V9l2.5 1L20 6l-4-2.5C15 5 13.5 5.5 12 5.5S9 5 8 3.5z"/>
            </svg>
            <span style="font-size: 9px; font-weight: 800; color: #f7f3ee; letter-spacing: 0.05em; margin-top: 2px;">{{ $num }}</span>
        </div>
    @else
        {{-- Versi Utama: Tema Zada Karya Production (Deep Maroon, Warm Gold, Garment Motif) --}}
        <div {{ $attributes->merge(['class' => 'relative flex flex-col justify-between overflow-hidden p-5 sm:p-6 select-none ' . $class]) }}
             style="background-color: #420903; background: radial-gradient(circle at 15% 20%, #5a0d04 0%, #420903 45%, #250401 100%); color: #ffffff; border-bottom: 3px solid #b8895a;">
            
            {{-- Latar Dekoratif Khas Zada Karya: Garis Shirt Apparel, Dot Matrix, dan Diagonal Ribbon --}}
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
                {{-- Dot Matrix Pattern (meniru tekstur anyaman kain / garment weave) --}}
                <svg class="absolute inset-0 h-full w-full" fill="none">
                    <defs>
                        <pattern id="{{ $patDotsId }}" width="14" height="14" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="1.2" fill="rgba(255, 255, 255, 0.045)"></circle>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#{{ $patDotsId }})"></rect>
                </svg>

                {{-- Watermark Ikon Kemeja / Apparel Zada Karya di Kanan Atas --}}
                <svg class="absolute -right-4 -top-4 h-36 w-36 sm:h-44 sm:w-44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.7"
                     style="color: rgba(200, 162, 118, 0.12); transform: rotate(-8deg);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 3.5 4 6l1.5 4L8 9v11.5h8V9l2.5 1L20 6l-4-2.5C15 5 13.5 5.5 12 5.5S9 5 8 3.5z"/>
                </svg>

                {{-- Bilah Geometris Diagonal Maroon-Warm Gold (Signature Zada Karya Hero motif) --}}
                <svg class="absolute bottom-0 right-0 h-full w-[45%] max-w-[200px]" viewBox="0 0 420 220" preserveAspectRatio="xMaxYMax slice" fill="none">
                    <defs>
                        <linearGradient id="{{ $gradBarId }}" x1="1" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#b34a3a" stop-opacity="0.6"></stop>
                            <stop offset="1" stop-color="#5a0d04" stop-opacity="0.9"></stop>
                        </linearGradient>
                    </defs>
                    <path d="M420 74 L420 148 L280 220 L186 220 Z" fill="url(#{{ $gradBarId }})"></path>
                    <path d="M420 168 L420 196 L360 220 L306 220 Z" fill="#8b2a1e" opacity="0.45"></path>
                    <path d="M420 208 L420 220 L398 220 L372 220 Z" fill="#c8a276" opacity="0.65"></path>
                </svg>
            </div>

            {{-- Atas: Kategori Pill Badge bergaya Zada Karya (Cream, Warm Gold Border, Deep Maroon text) --}}
            <div class="relative z-10 flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 font-extrabold uppercase tracking-wider"
                      style="background: rgba(253, 245, 243, 0.95); color: #5a0d04; border: 1px solid rgba(200, 162, 118, 0.5); font-size: 10px; border-radius: 6px; letter-spacing: 0.12em; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                    <span style="display: inline-block; width: 5px; height: 5px; border-radius: 50%; background-color: #6c1005;"></span>
                    {{ $catUpper }}
                </span>
            </div>

            {{-- Tengah: Judul Artikel (High contrast white typography, bold, professional) --}}
            <div class="relative z-10 my-auto py-2">
                <h4 class="font-extrabold leading-snug line-clamp-3"
                    style="color: #ffffff; font-size: 1.08rem; font-weight: 800; line-height: 1.35; letter-spacing: -0.015em; text-shadow: 0 2px 8px rgba(0,0,0,0.35);">
                    {{ $article->title }}
                </h4>
            </div>

            {{-- Bawah: Brand Identitas Zada Karya Production & Nomor Terbit Beraksen Gold --}}
            <div class="relative z-10 flex items-end justify-between pt-3"
                 style="border-top: 1px solid rgba(200, 162, 118, 0.28);">
                <div class="flex items-center gap-1.5" style="font-size: 11px; letter-spacing: 0.03em;">
                    <span style="color: #fdf5f3; font-weight: 700; text-transform: uppercase;">ZADA KARYA</span>
                    <span style="color: #c8a276;">•</span>
                    <span style="color: rgba(255, 255, 255, 0.7); font-weight: 500;">Konveksi & Garment</span>
                </div>
                <span style="font-size: 2.2rem; font-weight: 900; line-height: 0.9; color: rgba(200, 162, 118, 0.32); letter-spacing: -0.05em;">
                    {{ $num }}
                </span>
            </div>
        </div>
    @endif
@endif
