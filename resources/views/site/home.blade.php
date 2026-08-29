@extends('layouts.site')

@section('title', setting('seo_title', 'Zada Karya Production — Jasa Konveksi & Garment Custom'))
@section('meta_description', setting('seo_description'))

@section('content')

@php
    // Ikon garis (Heroicons v2 outline) dipakai di hero, panel, dan bar statistik.
    $ic = [
        'shirt' => 'M8 3.5 4 6l1.5 4L8 9v11.5h8V9l2.5 1L20 6l-4-2.5C15 5 13.5 5.5 12 5.5S9 5 8 3.5z',
        'badge' => 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12z',
        'palette' => 'M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'fabric' => 'M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3',
        'box' => 'M21 7.5l-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z',
        'truck' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        'headset' => 'M4.5 13.5V12a7.5 7.5 0 0 1 15 0v1.5m-15 0A1.5 1.5 0 0 0 3 15v2.25a1.5 1.5 0 0 0 1.5 1.5H6a1.5 1.5 0 0 0 1.5-1.5V15A1.5 1.5 0 0 0 6 13.5H4.5zm15 0A1.5 1.5 0 0 1 21 15v2.25a1.5 1.5 0 0 1-1.5 1.5H18a1.5 1.5 0 0 1-1.5-1.5V15a1.5 1.5 0 0 1 1.5-1.5h1.5zm-1.5 5.25v.75a2.25 2.25 0 0 1-2.25 2.25H13.5',
    ];
    $starPath = 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.563.563 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5z';
    $statIcons = ['users', 'shirt', 'badge', 'truck', 'headset'];
@endphp

{{-- ============================ HERO ============================ --}}
<section class="relative overflow-hidden bg-white">
    {{-- Dekorasi latar --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 z-0">
        <div class="absolute -left-64 -top-40 h-[820px] w-[820px] rounded-full bg-[radial-gradient(circle_at_center,var(--color-brand-50)_0%,rgba(253,245,243,0)_70%)]"></div>
        <svg class="absolute -right-32 -top-52 h-[620px] w-[620px] text-brand-50" viewBox="0 0 620 620" fill="none"><path d="M40 110C210 20 470 50 600 360" stroke="currentColor" stroke-width="110" stroke-linecap="round"/></svg>
        <svg class="absolute -bottom-32 -left-24 hidden h-[460px] w-[460px] text-brand-100/60 sm:block" viewBox="0 0 460 460" fill="none"><path d="M-10 440C140 420 320 310 440 60" stroke="currentColor" stroke-width="30" stroke-linecap="round"/></svg>
        <svg class="absolute left-3 top-[26%] hidden h-[88px] w-[32px] text-brand-500/70 lg:block" viewBox="0 0 32 88" fill="currentColor">
            <defs><pattern id="heroDots" width="14" height="14" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="2"/></pattern></defs>
            <rect width="32" height="88" fill="url(#heroDots)"/>
        </svg>
    </div>

    <div class="hero-grid relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- ---------- Kolom teks ---------- --}}
        <div class="hero-copy" data-reveal>
            <p class="inline-flex items-center rounded-full border border-brand-100 bg-brand-50 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.12em] text-brand-600 sm:text-xs">{{ $hero['badge'] }}</p>

            {{-- Ukuran per breakpoint dipilih agar baris aksen tetap muat satu baris di kolomnya. --}}
            <h1 class="mt-6 text-[34px] font-extrabold leading-[1.09] tracking-[-0.03em] text-ink sm:text-[42px] lg:text-[36px] xl:text-[46px]">
                {!! nl2br(e($hero['title'])) !!}<br>
                <span class="text-brand-600">{{ $hero['title_accent'] }}</span>
            </h1>

            <p class="mt-5 max-w-[30rem] text-base leading-relaxed text-neutral-600 sm:text-[17px]">{{ $hero['text'] }}</p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('consultation.create') }}"
                   class="inline-flex h-14 w-full items-center justify-center gap-2.5 rounded-xl bg-brand-600 px-6 text-[15px] font-bold text-white shadow-[0_14px_28px_-14px_rgba(108,16,5,.75)] transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 sm:w-auto">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic['shirt'] }}"/></svg>
                    Buat Pesanan Sekarang
                </a>
                <a href="{{ route('portfolio.index') }}"
                   class="inline-flex h-14 w-full items-center justify-center gap-2 rounded-xl border-[1.5px] border-brand-600 bg-white px-6 text-[15px] font-bold text-brand-600 transition hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 sm:w-auto">
                    Lihat Koleksi
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>

            <ul class="mt-9 grid gap-4 sm:grid-cols-3">
                @foreach([['badge', 'Kualitas Terjamin', 'Jahitan rapi & bahan premium'], ['palette', 'Desain Bebas', 'Sesuai ide & kebutuhanmu'], ['clock', 'Pengerjaan Cepat', 'Tepat waktu & bisa express']] as [$key, $title, $sub])
                    <li class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-line bg-white text-brand-600 shadow-[0_6px_14px_-8px_rgba(32,32,32,.3)]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic[$key] }}"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[13px] font-bold text-ink">{{ $title }}</span>
                            <span class="block text-[11px] leading-snug text-neutral-500">{{ $sub }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- ---------- Kolom visual ---------- --}}
        @php $framed = ! ($hero['image'] && $hero['style'] === 'cutout'); @endphp
        <div class="hero-visual">
            <div class="hero-stage {{ $framed ? 'hero-stage--framed' : '' }}">
                {{-- Bidang maroon abstrak + gema garis --}}
                <svg class="hero-backdrop" viewBox="0 0 520 560" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                    <defs>
                        <linearGradient id="heroGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="var(--color-brand-600)"/>
                            <stop offset="1" stop-color="var(--color-brand-400)"/>
                        </linearGradient>
                    </defs>
                    <rect x="60" y="46" width="404" height="474" rx="52" fill="url(#heroGrad)" transform="rotate(-8 260 280) skewX(-6)"/>
                    <rect x="60" y="46" width="404" height="474" rx="52" fill="none" stroke="var(--color-brand-400)" stroke-width="2" opacity=".5" transform="translate(38 -32) rotate(-8 260 280) skewX(-6)"/>
                </svg>

                {{-- Foto: upload admin > cover portfolio > ilustrasi --}}
                @if($hero['image'] && $hero['style'] === 'cutout')
                    <div class="hero-photo-wrap">
                        <img src="{{ asset('storage/'.$hero['image']) }}"
                             alt="Model mengenakan hasil produksi {{ setting('company_name', 'Zada Karya Production') }}"
                             fetchpriority="high" decoding="async" class="hero-photo">
                    </div>
                @elseif($hero['image'])
                    <div class="hero-photo-wrap hero-photo-wrap--framed">
                        <span class="hero-photo-frame">
                            <img src="{{ asset('storage/'.$hero['image']) }}"
                                 alt="Hasil produksi {{ setting('company_name', 'Zada Karya Production') }}"
                                 fetchpriority="high" decoding="async" class="hero-photo-framed">
                        </span>
                    </div>
                @elseif($hero['portfolio'])
                    <div class="hero-photo-wrap hero-photo-wrap--framed">
                        <a href="{{ route('portfolio.show', $hero['portfolio']) }}" class="hero-photo-frame">
                            <img src="{{ asset('storage/'.$hero['portfolio']->cover_image) }}"
                                 alt="{{ $hero['portfolio']->title }}" fetchpriority="high" decoding="async" class="hero-photo-framed">
                        </a>
                    </div>
                @else
                    <div class="hero-photo-wrap hero-photo-wrap--fallback" aria-hidden="true">
                        <svg class="h-1/2 w-1/2 text-white/85" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic['shirt'] }}"/>
                            <path stroke-linecap="round" d="M9.5 13.5h5M9.5 16.5h5" opacity=".55"/>
                        </svg>
                    </div>
                @endif

                {{-- Kartu melayang --}}
                <ul class="hero-cards" data-reveal-stagger>
                    @foreach([['shirt', 'Custom Sesukamu', 'Desain, warna, logo bebas pilih'], ['fabric', 'Pilihan Bahan Berkualitas', 'Nyaman, awet, dan premium'], ['box', 'Cocok untuk Segala Kebutuhan', 'Pribadi, komunitas, event, hingga jualan']] as [$key, $title, $sub])
                        <li class="flex items-start gap-3 rounded-2xl bg-white p-4 shadow-[0_16px_36px_-16px_rgba(32,32,32,.3)] ring-1 ring-black/[0.04]">
                            <svg class="mt-0.5 h-7 w-7 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic[$key] }}"/></svg>
                            <span class="min-w-0">
                                <span class="block text-[13px] font-bold leading-snug text-ink">{{ $title }}</span>
                                <span class="mt-0.5 block text-[11px] leading-snug text-neutral-500">{{ $sub }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>

                {{-- Pill rating --}}
                <div class="hero-rating flex items-center gap-3 rounded-full bg-white py-2.5 pl-3 pr-2.5 shadow-[0_16px_36px_-16px_rgba(32,32,32,.3)] ring-1 ring-black/[0.04]">
                    <div class="flex shrink-0 -space-x-2">
                        @foreach($hero['avatars'] as $avatar)
                            <span class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white text-xs font-bold text-white {{ $avatar['color'] }}">{{ $avatar['initial'] }}</span>
                        @endforeach
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-bold text-ink xl:text-sm">{{ $hero['rating_text'] }}</p>
                        <p class="truncate text-[11px] text-neutral-500">{{ $hero['rating_subtext'] }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $starPath }}"/></svg>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================== Panel "Apa yang ingin kamu buat?" ================== --}}
@if($panelServices->isNotEmpty())
<section class="relative z-20 mx-auto -mt-8 max-w-7xl px-4 sm:px-6 lg:-mt-[60px] lg:px-8" data-reveal>
    <div class="rounded-2xl border border-line/70 bg-white p-5 shadow-[0_24px_60px_-28px_rgba(32,32,32,.35)] sm:p-7 lg:flex lg:items-center lg:gap-8">
        <div class="shrink-0 lg:w-[210px]">
            <h2 class="text-2xl font-extrabold leading-tight tracking-tight text-ink">Apa yang ingin<br class="hidden lg:block"> kamu buat?</h2>
            <p class="mt-2 text-[13px] leading-relaxed text-neutral-500">Kami siap produksi sesuai kebutuhanmu.</p>
            <a href="{{ route('portfolio.index') }}" class="mt-3 inline-flex items-center gap-1.5 text-[13px] font-bold text-brand-600 hover:underline">
                Lihat Semua Koleksi
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
        <ul class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:mt-0 lg:min-w-0 lg:flex-1 xl:grid-cols-5" data-reveal-stagger>
            @foreach($panelServices as $service)
                <li>
                    <a href="{{ route('services.show', $service) }}" class="group flex h-full items-center gap-3 rounded-xl bg-cream p-3 transition hover:bg-brand-50">
                        @if($service->featured_image)
                            <img src="{{ asset('storage/'.$service->featured_image) }}" alt="" loading="lazy" class="h-14 w-14 shrink-0 rounded-lg object-cover xl:h-16 xl:w-16">
                        @else
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-600 xl:h-16 xl:w-16">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic['shirt'] }}"/></svg>
                            </span>
                        @endif
                        <span class="min-w-0">
                            <span class="block text-sm font-bold leading-tight text-ink group-hover:text-brand-600">{{ $service->name }}</span>
                            <span class="mt-0.5 block text-[11px] leading-snug text-neutral-500">{{ \Illuminate\Support\Str::limit($service->short_description, 28) }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

{{-- ============================ Statistik ============================ --}}
@if(count($heroStats))
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
    <ul class="grid grid-cols-2 gap-y-8 sm:grid-cols-3 lg:grid-cols-5 lg:divide-x lg:divide-line" data-reveal-stagger>
        @foreach($heroStats as $i => $stat)
            <li class="flex flex-col items-center gap-2.5 px-3 text-center xl:flex-row xl:justify-center xl:gap-3 xl:px-2 xl:text-left">
                <svg class="h-9 w-9 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic[$statIcons[$i] ?? 'badge'] }}"/></svg>
                <span class="min-w-0">
                    <span class="block text-xl font-extrabold leading-tight tracking-tight text-ink">{{ $stat['value'] }}</span>
                    <span class="mt-1 block text-[13px] leading-snug text-neutral-500">{{ $stat['label'] }}</span>
                </span>
            </li>
        @endforeach
    </ul>
</section>
@endif

{{-- Section "Layanan Unggulan" sengaja tidak ada di beranda: layanan sudah
     tampil sebagai panel "Apa yang ingin kamu buat?" tepat di bawah hero. --}}

{{-- Why Choose Us --}}
<section class="border-y border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-2xl" data-reveal>
            <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Kenapa Zada Karya</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-ink">Produksi Terukur, Hasil Konsisten</h2>
        </div>
        <div class="mt-10 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger>
            @foreach([
                ['title' => 'Produksi Custom', 'desc' => 'Model, ukuran, bahan, dan desain menyesuaikan kebutuhan Anda — bukan sebaliknya.'],
                ['title' => 'Konsultasi Kebutuhan', 'desc' => 'Tim kami membantu menentukan bahan dan model paling tepat sebelum produksi dimulai.'],
                ['title' => 'Pilihan Bahan Lengkap', 'desc' => 'Drill, tropical, lacoste, combed, dryfit, dan bahan lain sesuai budget dan pemakaian.'],
                ['title' => 'Proses Terstruktur', 'desc' => 'Tujuh tahap produksi yang jelas — Anda bisa memantau progress pesanan kapan saja.'],
                ['title' => 'Quality Check', 'desc' => 'Setiap produk diperiksa sebelum dikemas agar kualitas tetap konsisten.'],
                ['title' => 'Beragam Kebutuhan Apparel', 'desc' => 'Seragam, polo, kaos, celana, hingga produksi garment custom lainnya.'],
            ] as $i => $item)
                <div class="flex gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-extrabold text-white">{{ sprintf('%02d', $i + 1) }}</span>
                    <div>
                        <h3 class="font-bold text-ink">{{ $item['title'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-neutral-600">{{ $item['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Proses Produksi --}}
<section id="cara-order" class="mx-auto max-w-7xl scroll-mt-28 px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="max-w-2xl" data-reveal>
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Cara Order</p>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-ink">Proses Produksi Kami</h2>
    </div>
    <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-6" data-reveal-stagger>
        @foreach(['Konsultasi', 'Penawaran', 'Persetujuan', 'Produksi', 'Quality Check', 'Pengiriman'] as $i => $step)
            <li class="relative rounded-xl border border-line bg-white p-5 lg:p-4">
                <span class="text-3xl font-extrabold text-brand-100">{{ sprintf('%02d', $i + 1) }}</span>
                <h3 class="mt-2 text-sm font-bold text-ink">{{ $step }}</h3>
                @unless($loop->last)
                    <svg class="absolute -right-4 top-1/2 hidden h-4 w-4 -translate-y-1/2 text-warm-500 lg:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5"/></svg>
                @endunless
            </li>
        @endforeach
    </ol>
</section>

{{-- Portfolio --}}
@if($featuredPortfolios->isNotEmpty())
<section class="border-y border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Hasil Produksi</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-ink">Portfolio Terbaru</h2>
            </div>
            <a href="{{ route('portfolio.index') }}" class="btn-outline !py-2.5">Lihat Semua Portfolio</a>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger>
            @foreach($featuredPortfolios as $portfolio)
                <x-portfolio-card :portfolio="$portfolio" />
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Klien / Dipercaya oleh — ditaruh di blok bukti sosial (setelah Portfolio,
     sebelum Testimoni) supaya tidak berdempetan dengan bar statistik hero. --}}
@if($clients->isNotEmpty())
<section class="border-t border-line bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8" data-reveal>
        <p class="text-center text-xs font-bold uppercase tracking-widest text-neutral-500">Dipercaya oleh</p>
        <x-client-marquee :clients="$clients" class="mt-7" />
    </div>
</section>
@endif

{{-- Testimoni --}}
@if($reviews->isNotEmpty())
<section id="testimoni" class="scroll-mt-28 border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20" data-reveal>
        <x-review-carousel :reviews="$reviews" />
    </div>
</section>
@endif

{{-- CTA --}}
<section class="bg-brand-600">
    <div class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8" data-reveal>
        <h2 class="mx-auto max-w-2xl text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Punya kebutuhan konveksi?</h2>
        <p class="mx-auto mt-4 max-w-xl text-lg text-white/80">Konsultasikan kebutuhan produksi Anda bersama Zada Karya Production.</p>
        <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}"
           target="_blank" rel="noopener"
           onclick="if(window.gtag){gtag('event','whatsapp_click');}"
           class="btn-wa mt-8 !px-8 !py-3.5 !text-base">
            Konsultasi via WhatsApp
        </a>
    </div>
</section>

{{-- Blog tidak ditampilkan di beranda; artikel dibaca lewat menu Blog. --}}

@endsection
