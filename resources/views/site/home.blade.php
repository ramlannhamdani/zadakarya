@extends('layouts.site')

@section('title', setting('seo_title', 'Zada Karya Production — Jasa Konveksi & Garment Custom'))
@section('meta_description', setting('seo_description'))

@section('content')

{{-- Hero --}}
<section class="border-b border-line bg-cream">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
        <div data-reveal>
            <p class="inline-flex items-center gap-2 rounded-full border border-warm-500/40 bg-white px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-warm-600">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                Jasa Konveksi &amp; Garment Custom
            </p>
            <h1 class="mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight text-ink sm:text-5xl">
                Solusi Produksi <span class="text-brand-600">Konveksi</span> untuk Kebutuhan Anda
            </h1>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-neutral-600">
                Produksi seragam, apparel, kaos, polo, celana, dan kebutuhan garment custom dengan proses yang terukur — dari konsultasi hingga pengiriman.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('consultation.create') }}" class="btn-primary">Konsultasi Sekarang</a>
                <a href="{{ route('portfolio.index') }}" class="btn-outline">Lihat Portfolio</a>
            </div>
            <dl class="mt-10 grid grid-cols-3 gap-6 border-t border-line pt-8">
                <div>
                    <dt class="text-2xl font-extrabold text-brand-600">7 Tahap</dt>
                    <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-neutral-500">Tracking Produksi</dd>
                </div>
                <div>
                    <dt class="text-2xl font-extrabold text-brand-600">Custom</dt>
                    <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-neutral-500">Desain &amp; Bahan</dd>
                </div>
                <div>
                    <dt class="text-2xl font-extrabold text-brand-600">QC</dt>
                    <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-neutral-500">Setiap Pesanan</dd>
                </div>
            </dl>
        </div>
        <div class="grid grid-cols-2 gap-4" data-reveal-stagger>
            {{-- Slot terisi cover portfolio bergambar (featured dulu); sisanya placeholder --}}
            @foreach(['Seragam', 'Polo Shirt', 'Kaos Sablon', 'Custom'] as $i => $label)
                @php $p = $heroPortfolios[$i] ?? null; @endphp
                @if($p)
                    <a href="{{ route('portfolio.show', $p) }}" class="{{ $i % 2 === 0 ? '' : 'mt-8' }}">
                        <img src="{{ asset('storage/'.$p->cover_image) }}" alt="{{ $p->title }}" loading="lazy" class="aspect-[4/5] w-full rounded-xl object-cover">
                    </a>
                @else
                    <x-placeholder-image :label="$label" class="{{ $i % 2 === 0 ? '' : 'mt-8' }} aspect-[4/5] w-full rounded-xl" />
                @endif
            @endforeach
        </div>
    </div>
</section>

{{-- Layanan Unggulan --}}
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Layanan Kami</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-ink">Layanan Konveksi Unggulan</h2>
        </div>
        <a href="{{ route('services.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Semua Layanan &rarr;</a>
    </div>
    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-reveal-stagger>
        @foreach($services as $service)
            <x-service-card :service="$service" />
        @endforeach
    </div>
</section>

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
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="max-w-2xl" data-reveal>
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Cara Kerja</p>
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

{{-- Blog --}}
@if($articles->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Blog</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-ink">Artikel Terbaru</h2>
        </div>
        <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Semua Artikel &rarr;</a>
    </div>
    <div class="mt-8 grid gap-5 md:grid-cols-3" data-reveal-stagger>
        @foreach($articles as $article)
            <x-article-card :article="$article" />
        @endforeach
    </div>
</section>
@endif

@endsection
