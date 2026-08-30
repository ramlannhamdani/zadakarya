@props([
    'eyebrow' => null,
    'title',
    'text' => null,
    'icon' => null,   // shirt | photo | camera | document | factory | headset | package
])

@php
    // Ilustrasi garis besar di sisi kanan — satu bentuk per halaman.
    $illustrations = [
        'shirt' => 'M8 3.5 4 6l1.5 4L8 9v11.5h8V9l2.5 1L20 6l-4-2.5C15 5 13.5 5.5 12 5.5S9 5 8 3.5z',
        'photo' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 6.75h.008v.008H18V6.75zm3.75 11.25a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5h16.5A1.5 1.5 0 0 1 21.75 6v12z',
        'camera' => 'M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316zM16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0z',
        'document' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
        'factory' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z',
        'headset' => 'M4.5 13.5V12a7.5 7.5 0 0 1 15 0v1.5m-15 0A1.5 1.5 0 0 0 3 15v2.25a1.5 1.5 0 0 0 1.5 1.5H6a1.5 1.5 0 0 0 1.5-1.5V15A1.5 1.5 0 0 0 6 13.5H4.5zm15 0A1.5 1.5 0 0 1 21 15v2.25a1.5 1.5 0 0 1-1.5 1.5H18a1.5 1.5 0 0 1-1.5-1.5V15a1.5 1.5 0 0 1 1.5-1.5h1.5zm-1.5 5.25v.75a2.25 2.25 0 0 1-2.25 2.25H13.5',
        'package' => 'M21 7.5l-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
    ];
    $illustration = $illustrations[$icon] ?? null;
@endphp

<section {{ $attributes->merge(['class' => 'relative overflow-hidden border-b border-line bg-gradient-to-r from-cream via-cream/70 to-white']) }}>
    {{-- Dekorasi kanan: chevron tipis, dot grid, ilustrasi, dan bilah maroon di sudut --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <svg class="absolute right-[16%] top-1/2 hidden h-[320px] w-[320px] -translate-y-1/2 text-brand-200/60 lg:block" viewBox="0 0 200 200" fill="none">
            <path d="M60 12 L148 100 L60 188" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 40 L78 100 L18 160" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity=".55"/>
        </svg>

        <svg class="absolute right-[6%] top-7 hidden h-[74px] w-[52px] text-brand-300/70 md:block" viewBox="0 0 52 74" fill="currentColor">
            <defs><pattern id="phDots-{{ $icon ?? 'x' }}" width="13" height="13" patternUnits="userSpaceOnUse"><circle cx="1.8" cy="1.8" r="1.8"/></pattern></defs>
            <rect width="52" height="74" fill="url(#phDots-{{ $icon ?? 'x' }})"/>
        </svg>

        @if($illustration)
            <svg class="absolute right-[7%] top-1/2 hidden h-[190px] w-[190px] -translate-y-1/2 text-brand-300/45 md:block lg:h-[210px] lg:w-[210px]"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.55">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $illustration }}"/>
            </svg>
        @endif

        {{-- Bilah diagonal maroon di sudut kanan bawah --}}
        {{-- Di ponsel bilah dibatasi ke pita bawah supaya tidak menimpa teks --}}
        <svg class="absolute bottom-0 right-0 h-[88px] w-[62%] sm:h-full sm:w-[38%] sm:max-w-[360px]" viewBox="0 0 420 220" preserveAspectRatio="xMaxYMax slice" fill="none">
            <defs>
                <linearGradient id="phBar-{{ $icon ?? 'x' }}" x1="1" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="var(--color-brand-400)"/>
                    <stop offset="1" stop-color="var(--color-brand-700)"/>
                </linearGradient>
            </defs>
            <path d="M420 74 L420 148 L280 220 L186 220 Z" fill="url(#phBar-{{ $icon ?? 'x' }})"/>
            <path d="M420 168 L420 196 L360 220 L306 220 Z" fill="var(--color-brand-500)" opacity=".8"/>
            <path d="M420 208 L420 220 L398 220 L372 220 Z" fill="var(--color-brand-300)" opacity=".9"/>
        </svg>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 pb-24 pt-12 sm:px-6 sm:py-14 lg:px-8 lg:py-16">
        <div class="max-w-[34rem]" data-reveal>
            @if($eyebrow)
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand-600 sm:text-xs">{{ $eyebrow }}</p>
                <span class="mt-2 block h-[3px] w-9 rounded-full bg-brand-600"></span>
            @endif

            <h1 class="mt-4 text-[28px] font-extrabold leading-[1.15] tracking-tight text-ink sm:text-4xl lg:text-[40px]">{!! nl2br(e($title)) !!}</h1>

            @if($text)
                <p class="mt-4 max-w-lg text-[15px] leading-relaxed text-neutral-600 sm:text-base">{{ $text }}</p>
            @endif

            {{ $slot }}
        </div>
    </div>
</section>
