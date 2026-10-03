<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $siteName }} — Official Biolink &amp; Social Hub</title>
    <meta name="description" content="Temukan kontak resmi, katalog media sosial, konsultasi konveksi custom apparel, dan lokasi workshop {{ $siteName }} dalam satu halaman.">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $siteName }} — Official Biolink">
    <meta property="og:description" content="Katalog layanan konveksi, konsultasi WhatsApp, sosial media, dan lokasi workshop {{ $siteName }}.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/biolink/mockup-reference.jpg') }}">

    @if(setting('favicon'))
        <link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">
    @elseif(setting('logo'))
        <link rel="icon" href="{{ asset('storage/'.setting('logo')) }}">
    @endif

    {{-- Fonts: Plus Jakarta Sans for clean typography, Caveat for organic handwriting --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if(setting('analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ setting('analytics_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ setting('analytics_id') }}');
        </script>
    @endif

    <style>
        /* Styling khusus biolink */
        body {
            background-color: #1a1715;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        .biolink-paper-bg {
            background-color: #eee6db;
            background-image: 
                radial-gradient(#d3c5b3 0.65px, transparent 0.65px),
                radial-gradient(#ded1be 0.65px, #eee6db 0.65px);
            background-size: 24px 24px;
            background-position: 0 0, 12px 12px;
        }

        .btn-maroon {
            background: linear-gradient(135deg, #570e05 0%, #681206 50%, #520d04 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            box-shadow: 0 6px 16px -3px rgba(87, 14, 5, 0.38), 0 2px 6px -1px rgba(87, 14, 5, 0.2);
        }
        .btn-maroon:hover {
            background: linear-gradient(135deg, #681206 0%, #7a1608 50%, #5d0f04 100%);
            box-shadow: 0 8px 20px -3px rgba(87, 14, 5, 0.48), 0 3px 8px -1px rgba(87, 14, 5, 0.25);
        }

        .btn-light {
            background: #ffffff;
            border: 1px solid #e2dad0;
            color: #1c1917;
            box-shadow: 0 4px 14px -3px rgba(0, 0, 0, 0.05), 0 2px 5px -1px rgba(0, 0, 0, 0.03);
        }
        .btn-light:hover {
            background: #faf7f2;
            border-color: #d6ccbf;
            box-shadow: 0 6px 18px -3px rgba(0, 0, 0, 0.08), 0 3px 6px -1px rgba(0, 0, 0, 0.04);
        }

        .font-handwriting {
            font-family: 'Caveat', cursive, sans-serif;
        }

        /* Contour lines overlay */
        .contour-overlay {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='200' viewBox='0 0 400 200'%3E%3Cpath d='M-50,80 Q50,40 150,90 T350,70 T550,110 M-50,110 Q60,70 170,120 T360,100 T550,140 M-50,140 Q80,100 190,150 T380,130 T550,170 M-50,50 Q40,20 130,60 T320,40 T550,80 M-50,170 Q90,130 210,180 T400,160 T550,200' fill='none' stroke='%23ffffff' stroke-width='0.75' stroke-opacity='0.08'/%3E%3C/svg%3E");
            background-repeat: repeat;
        }
    </style>
</head>
<body class="min-h-screen bg-[#1a1715] flex flex-col sm:items-center justify-start p-0 sm:py-6 selection:bg-[#6c1206] selection:text-white overflow-x-hidden">

    {{-- Main Mobile Container (Centered on desktop, full-width edge-to-edge on mobile) --}}
    <main class="w-full sm:max-w-[420px] min-h-screen sm:min-h-0 sm:rounded-[32px] overflow-hidden biolink-paper-bg relative shadow-2xl flex flex-col justify-between border-0 sm:border sm:border-neutral-800/80">

        {{-- TOP SECTION: Header Banner Artwork --}}
        <header class="relative w-full overflow-hidden select-none">
            {{-- Original collage artwork (taped shirt, Zada Karya brush logo, embroidered mountain, notes) --}}
            <img src="{{ asset('images/biolink/header-banner.jpg') }}"
                 alt="Zada Karya — Clothing &amp; Custom Apparel — Dari Ide, Jadi Karya Nyata"
                 class="w-full h-auto block select-none pointer-events-none"
                 width="682"
                 height="268"
                 loading="eager" />

            {{-- Hidden SEO semantic markup --}}
            <div class="sr-only">
                <h1>{{ $siteName }}</h1>
                <p>{{ $tagline }}</p>
                <p>Dari Ide, Jadi Karya Nyata. Sablon, Bordir, Custom Apparel.</p>
            </div>
        </header>

        {{-- MIDDLE SECTION: Interactive Action Buttons with Flanking Process Cutouts --}}
        <section class="relative px-3.5 sm:px-4 py-2 z-10 flex-1 flex flex-col justify-center">

            {{-- Flanking background cutouts from original photo collage --}}
            <img src="{{ asset('images/biolink/screenprint-cutout.jpg') }}"
                 alt=""
                 aria-hidden="true"
                 class="absolute -left-1 top-14 w-24 sm:w-28 -z-10 pointer-events-none select-none drop-shadow-md opacity-85" />

            <img src="{{ asset('images/biolink/pouch-cutout.jpg') }}"
                 alt=""
                 aria-hidden="true"
                 class="absolute -right-1 top-36 w-28 sm:w-32 -z-10 pointer-events-none select-none drop-shadow-md opacity-85" />

            {{-- List of Action Buttons --}}
            <div class="space-y-3 relative z-10 w-full mx-auto">
                @foreach($links as $link)
                    @php
                        $isMaroon = ($link['theme'] === 'maroon');
                    @endphp
                    <a href="{{ $link['url'] }}"
                       id="{{ $link['id'] }}"
                       @if($link['is_external']) target="_blank" rel="noopener noreferrer" @endif
                       class="group relative flex items-center justify-between w-full px-3.5 sm:px-4 py-3 sm:py-3.5 rounded-[22px] transition-all duration-200 active:scale-[0.98] {{ $isMaroon ? 'btn-maroon' : 'btn-light' }}">

                        {{-- Left Circle Icon Container --}}
                        <div class="flex items-center gap-3.5 min-w-0">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-black/5 transition-transform duration-200 group-hover:scale-105">
                                @if($link['icon'] === 'globe')
                                    {{-- Globe SVG --}}
                                    <svg class="h-6 w-6 text-[#6c1206]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="2" y1="12" x2="22" y2="12"></line>
                                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                    </svg>
                                @elseif($link['icon'] === 'tiktok')
                                    {{-- Official TikTok SVG --}}
                                    <svg class="h-5 w-5 text-black" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                    </svg>
                                @elseif($link['icon'] === 'youtube')
                                    {{-- Official YouTube Red SVG --}}
                                    <svg class="h-6 w-6 text-[#FF0000]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                @elseif($link['icon'] === 'whatsapp')
                                    {{-- Official WhatsApp SVG --}}
                                    <svg class="h-6 w-6 {{ $isMaroon ? 'text-[#25D366]' : 'text-[#6c1206]' }}" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                @elseif($link['icon'] === 'maps')
                                    {{-- Official Google Maps 4-Color Pin SVG --}}
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="#EA4335"/>
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 2.38 1.19 5.35 3.04 8.44L12 22l3.96-4.56C17.81 14.35 19 11.38 19 9c0-3.87-3.13-7-7-7z" fill="#EA4335"/>
                                        <path d="M8.04 17.44C6.19 14.35 5 11.38 5 9c0-3.87 3.13-7 7-7v20l-3.96-4.56z" fill="#4285F4"/>
                                        <circle cx="12" cy="9" r="3" fill="#FFFFFF"/>
                                        <path d="M12 6a3 3 0 0 0-3 3c0 .8.32 1.53.84 2.06L12 9V6z" fill="#FBBC04"/>
                                        <path d="M12 12a3 3 0 0 0 3-3c0-.8-.32-1.53-.84-2.06L12 9v3z" fill="#34A853"/>
                                    </svg>
                                @endif
                            </span>

                            {{-- Text Info --}}
                            <div class="flex flex-col text-left leading-tight truncate">
                                <span class="font-bold text-[15px] sm:text-[16px] tracking-tight {{ $isMaroon ? 'text-white' : 'text-neutral-900' }}">
                                    {{ $link['title'] }}
                                </span>
                                <span class="text-[11px] sm:text-[12px] mt-0.5 truncate {{ $isMaroon ? 'text-[#f6dfdc]' : 'text-neutral-500' }}">
                                    {{ $link['subtitle'] }}
                                </span>
                            </div>
                        </div>

                        {{-- Right Chevron Arrow --}}
                        <div class="pl-2 shrink-0">
                            <svg class="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1 {{ $isMaroon ? 'text-white/80' : 'text-neutral-400' }}"
                                 fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- 4-Column Feature Highlights Card --}}
            <div class="mt-4 rounded-2xl border border-[#d6c7b5] bg-[#e6ddd1]/85 backdrop-blur-sm p-3 shadow-sm w-full mx-auto">
                <div class="grid grid-cols-4 divide-x divide-[#cfbfab] text-center">

                    {{-- 1. Custom Kaos --}}
                    <div class="flex flex-col items-center justify-center px-0.5 min-w-0">
                        <svg class="h-6 w-6 text-[#5c0f05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.47a2 2 0 001.37 1.58L6 11.3V20a2 2 0 002 2h8a2 2 0 002-2v-8.7l1.77-.56a2 2 0 001.37-1.58l.58-3.47a2 2 0 00-1.34-2.23z"/>
                        </svg>
                        <span class="mt-1.5 text-[10px] sm:text-[11px] font-bold leading-tight text-[#45362a]">Custom<br>Kaos</span>
                    </div>

                    {{-- 2. Sablon Manual & DTF --}}
                    <div class="flex flex-col items-center justify-center px-0.5 min-w-0">
                        <svg class="h-6 w-6 text-[#5c0f05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="15" rx="2" ry="2"></rect>
                            <line x1="3" y1="13" x2="21" y2="13"></line>
                            <line x1="12" y1="13" x2="12" y2="19"></line>
                        </svg>
                        <span class="mt-1.5 text-[10px] sm:text-[11px] font-bold leading-tight text-[#45362a]">Sablon<br>Manual &amp; DTF</span>
                    </div>

                    {{-- 3. Bordir Komputer (Needle & Embroidery Thread Loop) --}}
                    <div class="flex flex-col items-center justify-center px-0.5 min-w-0">
                        <svg class="h-6 w-6 text-[#5c0f05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 3l-8 8m-2 2l-4 4a1.4 1.4 0 002 2l4-4m-2-2l2-2"/>
                            <path d="M17.5 1.5a1.4 1.4 0 012 2l-1 1-2-2 1-1z"/>
                            <path d="M7 17c-2 2-3 3-3 4s1 2 2 1 2-2 3-4"/>
                            <circle cx="18" cy="4" r="0.5" fill="currentColor"/>
                        </svg>
                        <span class="mt-1.5 text-[10px] sm:text-[11px] font-bold leading-tight text-[#45362a]">Bordir<br>Komputer</span>
                    </div>

                    {{-- 4. Satuan & Partai --}}
                    <div class="flex flex-col items-center justify-center px-0.5 min-w-0">
                        <svg class="h-6 w-6 text-[#5c0f05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <span class="mt-1.5 text-[10px] sm:text-[11px] font-bold leading-tight text-[#45362a]">Satuan<br>&amp; Partai</span>
                    </div>

                </div>
            </div>

        </section>

        {{-- BOTTOM SECTION: Torn Paper Divider & Maroon Footer --}}
        <footer class="relative mt-4 w-full">

            {{-- Realistic Organic Torn Paper Edge SVG Divider --}}
            <div class="w-full overflow-hidden leading-none select-none -mb-px">
                <svg viewBox="0 0 1200 48" preserveAspectRatio="none" class="relative block w-full h-8 sm:h-11 text-[#540e06]" fill="currentColor">
                    <path d="M0,0 C25,12 40,8 70,18 C105,10 130,22 165,8 C195,19 220,11 250,20 C280,7 310,19 340,10 C370,21 400,9 430,17 C460,7 495,22 530,11 C560,20 595,8 630,19 C660,9 695,22 730,12 C760,19 790,8 820,20 C850,10 885,21 920,9 C955,20 990,11 1025,22 C1060,9 1095,19 1130,11 C1160,19 1180,12 1200,8 L1200,48 L0,48 Z"></path>
                </svg>
            </div>

            {{-- Dark Maroon Footer Body with Contour Lines Pattern --}}
            <div class="bg-[#540e06] contour-overlay text-white pt-3 pb-7 px-5 text-center relative z-10">

                {{-- Handwriting Thank You Note with Heart (2 lines, matches mockup) --}}
                <div class="font-handwriting leading-tight drop-shadow-sm">
                    <p class="text-[25px] sm:text-[28px] text-[#fbe3de] tracking-wide font-bold">
                        Terima kasih
                    </p>
                    <p class="text-[20px] sm:text-[22px] text-[#f4cfc7] tracking-wide -mt-0.5">
                        sudah mendukung karya lokal <span class="text-rose-300 inline-block transition-transform hover:scale-125">♡</span>
                    </p>
                </div>

                {{-- Clickable Social Icon Buttons --}}
                <div class="mt-4 flex items-center justify-center gap-3.5">

                    {{-- Instagram --}}
                    @if($instagramUrl)
                        <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer"
                           aria-label="Instagram Zada Karya"
                           class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition-all hover:bg-white hover:text-[#5c0f05] hover:scale-110 active:scale-95 shadow-sm">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                    @endif

                    {{-- TikTok --}}
                    @if($tiktokUrl)
                        <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer"
                           aria-label="TikTok Zada Karya"
                           class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition-all hover:bg-white hover:text-[#5c0f05] hover:scale-110 active:scale-95 shadow-sm">
                            <svg class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                            </svg>
                        </a>
                    @endif

                    {{-- YouTube --}}
                    @if($youtubeUrl)
                        <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener noreferrer"
                           aria-label="YouTube Zada Karya"
                           class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition-all hover:bg-white hover:text-[#5c0f05] hover:scale-110 active:scale-95 shadow-sm">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                    @endif

                    {{-- WhatsApp --}}
                    @if($admin1Link)
                        <a href="{{ $admin1Link }}" target="_blank" rel="noopener noreferrer"
                           aria-label="WhatsApp Zada Karya"
                           class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition-all hover:bg-white hover:text-[#5c0f05] hover:scale-110 active:scale-95 shadow-sm">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </a>
                    @endif

                </div>

                {{-- Spaced Brand Signature --}}
                <p class="mt-4 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.35em] text-[#d68a7f] select-none">
                    Z A D A &nbsp; K A R Y A
                </p>

            </div>

        </footer>

    </main>

</body>
</html>
