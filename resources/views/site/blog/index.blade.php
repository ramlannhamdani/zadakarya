@extends('layouts.site')

@section('title', 'Blog — '.setting('company_name'))
@section('meta_description', 'Artikel, tips, dan informasi seputar konveksi, bahan, sablon, dan produksi garment dari Zada Karya Production.')

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8" data-reveal>
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Blog</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-ink">Artikel &amp; Informasi</h1>
        <p class="mt-4 max-w-2xl text-neutral-600">Tips memilih bahan, teknik sablon, dan informasi seputar dunia konveksi.</p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('blog.index') }}"
               class="rounded-full px-4 py-2 text-sm font-semibold transition {{ !request('kategori') ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600 hover:border-brand-600 hover:text-brand-600' }}">Semua</a>
            @foreach($categories as $category)
                <a href="{{ route('blog.index', ['kategori' => $category->slug]) }}"
                   class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request('kategori') === $category->slug ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600 hover:border-brand-600 hover:text-brand-600' }}">{{ $category->name }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('blog.index') }}" class="flex gap-2">
            @if(request('kategori'))<input type="hidden" name="kategori" value="{{ request('kategori') }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari artikel..." class="form-input !w-56">
            <button type="submit" class="btn-primary !px-4 !py-2.5">Cari</button>
        </form>
    </div>

    <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3" data-reveal-stagger>
        @forelse($articles as $article)
            <x-article-card :article="$article" />
        @empty
            <p class="col-span-full py-10 text-center text-neutral-500">Tidak ada artikel ditemukan.</p>
        @endforelse
    </div>

    <div class="mt-10">{{ $articles->links() }}</div>
</section>
@endsection
