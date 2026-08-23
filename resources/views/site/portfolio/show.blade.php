@extends('layouts.site')

@section('title', ($portfolio->seo_title ?: $portfolio->title.' — Portfolio '.setting('company_name')))
@section('meta_description', $portfolio->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $portfolio->description), 155))
@if($portfolio->cover_image)
    @section('og_image', asset('storage/'.$portfolio->cover_image))
@endif

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-neutral-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('portfolio.index') }}" class="hover:text-brand-600">Portfolio</a>
            <span class="mx-1.5">/</span>
            <span class="text-ink">{{ $portfolio->title }}</span>
        </nav>
    </div>
</section>

<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center gap-3 text-sm">
        @if($portfolio->category)
            <span class="rounded-full bg-brand-100 px-3 py-1 font-semibold text-brand-600">{{ $portfolio->category->name }}</span>
        @endif
        @if($portfolio->production_year)
            <span class="text-neutral-500">Tahun Produksi: {{ $portfolio->production_year }}</span>
        @endif
        @if($portfolio->client_name)
            <span class="text-neutral-500">Klien: {{ $portfolio->client_name }}</span>
        @endif
    </div>

    <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">{{ $portfolio->title }}</h1>

    @if($portfolio->cover_image)
        <img src="{{ asset('storage/'.$portfolio->cover_image) }}" alt="{{ $portfolio->title }}" class="mt-8 w-full rounded-xl object-cover">
    @else
        <x-placeholder-image :label="$portfolio->category?->name" class="mt-8 aspect-[16/9] w-full rounded-xl" />
    @endif

    @if($portfolio->description)
        <div class="prose-zada mt-8">{!! nl2br(e($portfolio->description)) !!}</div>
    @endif

    @if($portfolio->images->isNotEmpty())
        <h2 class="mt-10 text-xl font-bold text-ink">Galeri</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach($portfolio->images as $image)
                <a href="{{ asset('storage/'.$image->image_path) }}" target="_blank" rel="noopener">
                    <img src="{{ asset('storage/'.($image->thumb_path ?: $image->image_path)) }}" alt="{{ $portfolio->title }}" loading="lazy" class="aspect-square w-full rounded-lg object-cover transition hover:opacity-90">
                </a>
            @endforeach
        </div>
    @endif

    @if($portfolio->tags)
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach($portfolio->tags as $tag)
                <span class="rounded-full border border-line bg-white px-3 py-1 text-xs font-medium text-neutral-600">#{{ $tag }}</span>
            @endforeach
        </div>
    @endif

    <div class="mt-10 rounded-xl bg-brand-600 p-8 text-center">
        <h2 class="text-xl font-bold text-white">Ingin hasil seperti ini untuk kebutuhan Anda?</h2>
        <a href="{{ wa_link('Halo Zada Karya Production, saya tertarik dengan portfolio "'.$portfolio->title.'" dan ingin berkonsultasi.') }}"
           target="_blank" rel="noopener" class="btn-wa mt-5">Konsultasi via WhatsApp</a>
    </div>
</section>

@if($related->isNotEmpty())
<section class="border-t border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink">Portfolio Lainnya</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($related as $item)
                <x-portfolio-card :portfolio="$item" />
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
