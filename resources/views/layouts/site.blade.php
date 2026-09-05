<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>document.documentElement.classList.add('js')</script>

    @php
        $siteName = setting('company_name', 'Zada Karya Production');
        $metaTitle = trim($__env->yieldContent('title')) ?: setting('seo_title', $siteName);
        $metaDescription = trim($__env->yieldContent('meta_description')) ?: setting('seo_description', '');
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    @if(setting('favicon'))
        <link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">
    @elseif(setting('logo'))
        <link rel="icon" href="{{ asset('storage/'.setting('logo')) }}">
    @endif

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

    @yield('schema')
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-white">

{{-- Navbar --}}
@php
    // Satu daftar menu untuk desktop dan panel mobile — tidak ada menu kedua.
    $nav = [
        ['label' => 'Beranda', 'href' => route('home'), 'match' => 'home'],
        ['label' => 'Koleksi', 'href' => route('portfolio.index'), 'match' => 'portfolio.*'],
        ['label' => 'Layanan', 'href' => route('services.index'), 'match' => 'services.*'],
        ['label' => 'Galeri', 'href' => route('gallery.index'), 'match' => 'gallery.*'],
        ['label' => 'Blog', 'href' => route('blog.index'), 'match' => 'blog.*'],
        ['label' => 'Tracking', 'href' => route('tracking.index'), 'match' => 'tracking.*'],
        ['label' => 'Tentang Kami', 'href' => route('about'), 'match' => 'about'],
        ['label' => 'Kontak', 'href' => route('contact'), 'match' => 'contact'],
    ];
    // Emblem = favicon (PNG). ICO tidak bisa dipakai <img>, jatuh ke kotak "ZK".
    $emblem = setting('favicon') && \Illuminate\Support\Str::endsWith(strtolower(setting('favicon')), ['.png', '.webp'])
        ? asset('storage/'.setting('favicon'))
        : null;
@endphp

<header x-data="{ open: false }" @keydown.escape.window="open = false" class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
    <div @click.outside="open = false" class="relative mx-auto flex h-20 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:h-24 lg:gap-6 lg:px-8">

        {{-- Brand --}}
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 sm:gap-2.5 lg:gap-3">
            @if($emblem)
                <img src="{{ $emblem }}" alt="" class="h-10 w-10 shrink-0 object-contain sm:h-11 sm:w-11 lg:h-14 lg:w-14">
            @elseif(setting('logo'))
                <img src="{{ asset('storage/'.setting('logo')) }}" alt="" class="h-9 w-auto shrink-0 sm:h-10 lg:h-12">
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-base font-extrabold text-white sm:h-11 sm:w-11 lg:h-14 lg:w-14 lg:text-lg">ZK</span>
            @endif
            <span class="flex min-w-0 flex-col leading-none">
                <span class="truncate text-[13px] font-extrabold uppercase tracking-tight text-ink sm:text-[15px] lg:text-xl">Zada Karya</span>
                <span class="mt-0.5 truncate text-[9px] font-bold uppercase tracking-[0.24em] text-ink sm:mt-1 sm:text-[10px] lg:text-[13px]">Production</span>
            </span>
            <span class="sr-only">{{ $siteName }}</span>
        </a>

        {{-- Menu (desktop lebar) --}}
        <nav class="hidden items-center gap-5 xl:flex xl:gap-6" aria-label="Navigasi utama">
            @foreach($nav as $link)
                @php $active = request()->routeIs($link['match']); @endphp
                <a href="{{ $link['href'] }}" @if($active) aria-current="page" @endif
                   class="relative whitespace-nowrap text-[15px] font-medium transition {{ $active
                        ? 'text-brand-600 after:absolute after:-bottom-2 after:left-0 after:h-[3px] after:w-7 after:rounded-full after:bg-brand-600 after:content-[\'\']'
                        : 'text-ink hover:text-brand-600' }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        {{-- CTA + hamburger (hamburger hanya saat menu desktop disembunyikan) --}}
        <div class="flex shrink-0 items-center gap-2 lg:gap-3">
            <a href="{{ route('consultation.create') }}"
               class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-full bg-brand-600 px-3.5 text-white transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 sm:h-11 sm:gap-2 sm:px-5 lg:h-13 lg:px-6">
                <svg class="h-[18px] w-[18px] shrink-0 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                {{-- Label dipendekkan di layar sempit supaya baris navbar tidak melebar --}}
                <span class="whitespace-nowrap text-[13px] font-bold sm:hidden">Konsultasi</span>
                <span class="hidden whitespace-nowrap font-bold sm:inline sm:text-[15px]">Konsultasi Gratis</span>
            </a>
            <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-controls="site-menu" aria-label="Menu"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-300 text-ink transition hover:border-brand-600 hover:text-brand-600 sm:h-11 sm:w-11 lg:h-13 lg:w-13 xl:hidden">
                <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        {{-- Panel menu: isi sama persis dengan menu desktop --}}
        <div id="site-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms
             class="absolute -inset-x-4 top-full z-50 max-h-[calc(100vh-5rem)] overflow-y-auto border-t border-line bg-white shadow-xl sm:-inset-x-6 lg:inset-x-auto lg:right-8 lg:mt-2 lg:w-72 lg:rounded-2xl lg:border lg:border-line xl:hidden">
            <nav class="px-4 py-3 sm:px-6 lg:px-3" aria-label="Menu">
                <ul class="space-y-1">
                    @foreach($nav as $link)
                        @php $active = request()->routeIs($link['match']); @endphp
                        <li>
                            <a href="{{ $link['href'] }}" @click="open = false" @if($active) aria-current="page" @endif
                               class="block rounded-lg px-3 py-2.5 text-[15px] font-medium {{ $active ? 'bg-brand-50 text-brand-600' : 'text-ink hover:bg-cream hover:text-brand-600' }}">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>
</header>

<main class="flex-1">
    @yield('content')
</main>

{{-- Footer --}}
<footer class="bg-brand-800 text-white">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="flex items-center gap-2.5">
                    @if(setting('logo_light'))
                        <img src="{{ asset('storage/'.setting('logo_light')) }}" alt="{{ $siteName }}" class="h-16 w-auto">
                        <span class="sr-only">{{ $siteName }}</span>
                    @else
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-sm font-extrabold text-white">ZK</span>
                        <span class="font-extrabold tracking-tight">Zada Karya Production</span>
                    @endif
                </div>
                <p class="mt-4 text-sm leading-relaxed text-white/70">{{ setting('footer_text') }}</p>
                <x-social-links class="mt-4" link-class="text-white/70 hover:text-white" />
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-warm-400">Navigasi</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="text-white/80 hover:text-white">Home</a></li>
                    <li><a href="{{ route('services.index') }}" class="text-white/80 hover:text-white">Layanan</a></li>
                    <li><a href="{{ route('portfolio.index') }}" class="text-white/80 hover:text-white">Portfolio</a></li>
                    <li><a href="{{ route('gallery.index') }}" class="text-white/80 hover:text-white">Galeri</a></li>
                    <li><a href="{{ route('blog.index') }}" class="text-white/80 hover:text-white">Blog</a></li>
                    <li><a href="{{ route('about') }}" class="text-white/80 hover:text-white">Tentang Kami</a></li>
                    <li><a href="{{ route('consultation.create') }}" class="text-white/80 hover:text-white">Konsultasi</a></li>
                    <li><a href="{{ route('tracking.index') }}" class="text-white/80 hover:text-white">Tracking</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-warm-400">Kontak</h3>
                <ul class="mt-4 space-y-3 text-sm text-white/80">
                    <li class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-warm-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span>{{ setting('address') }}</span>
                    </li>
                    <li class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-warm-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        <span>{{ setting('whatsapp') }}</span>
                    </li>
                    <li class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-warm-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        <span>{{ setting('email') }}</span>
                    </li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-warm-400">Punya kebutuhan konveksi?</h3>
                <p class="mt-4 text-sm text-white/70">Konsultasikan kebutuhan produksi Anda bersama kami — gratis dan tanpa komitmen.</p>
                <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}" target="_blank" rel="noopener" class="btn-wa mt-4">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Konsultasi via WhatsApp
                </a>
            </div>
        </div>
        <div class="mt-12 border-t border-white/10 pt-6 text-center text-xs text-white/50">
            &copy; {{ date('Y') }} {{ $siteName }}. Seluruh hak cipta dilindungi.
        </div>
    </div>
</footer>

{{-- Floating WhatsApp --}}
<a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}"
   target="_blank" rel="noopener" aria-label="Chat WhatsApp"
   onclick="if(window.gtag){gtag('event','whatsapp_click');}"
   class="fixed bottom-5 right-5 z-50 flex h-13 w-13 items-center justify-center rounded-full bg-[#25D366] p-3.5 text-white shadow-lg transition hover:scale-105">
    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<style>[x-cloak]{display:none!important}</style>
</body>
</html>
