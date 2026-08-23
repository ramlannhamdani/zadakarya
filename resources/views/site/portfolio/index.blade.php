@extends('layouts.site')

@section('title', 'Portfolio — '.setting('company_name'))
@section('meta_description', 'Hasil produksi Zada Karya Production: seragam, polo shirt, kaos sablon, jersey olahraga, celana, dan apparel custom.')

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Portfolio</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-ink">Hasil Produksi Kami</h1>
        <p class="mt-4 max-w-2xl text-neutral-600">Beberapa hasil produksi yang telah kami kerjakan untuk berbagai kebutuhan.</p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if($categories->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('portfolio.index') }}"
               class="rounded-full px-4 py-2 text-sm font-semibold transition {{ !request('kategori') ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600 hover:border-brand-600 hover:text-brand-600' }}">Semua</a>
            @foreach($categories as $category)
                <a href="{{ route('portfolio.index', ['kategori' => $category->slug]) }}"
                   class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request('kategori') === $category->slug ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600 hover:border-brand-600 hover:text-brand-600' }}">{{ $category->name }}</a>
            @endforeach
        </div>
    @endif

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($portfolios as $portfolio)
            <x-portfolio-card :portfolio="$portfolio" />
        @empty
            <p class="col-span-full py-10 text-center text-neutral-500">Belum ada portfolio pada kategori ini.</p>
        @endforelse
    </div>

    <div class="mt-10">{{ $portfolios->links() }}</div>
</section>

{{-- Ulasan Google Maps --}}
@if($reviews->isNotEmpty())
<section class="border-t border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <x-review-carousel :reviews="$reviews" />
    </div>
</section>
@endif
@endsection
