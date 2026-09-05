@extends('layouts.site')

@section('title', ($article->seo_title ?: $article->title.' — '.setting('company_name')))
@section('meta_description', $article->seo_description ?: $article->excerpt)
@if($article->og_image || $article->featured_image)
    @section('og_image', asset('storage/'.($article->og_image ?: $article->featured_image)))
@endif

@section('schema')
@php
    $breadcrumbItems = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => route('home'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Blog',
            'item' => route('blog.index'),
        ],
    ];

    if ($article->category) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $article->category->name,
            'item' => route('blog.index', ['kategori' => $article->category->slug]),
        ];
    }

    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumbItems) + 1,
        'name' => $article->title,
        'item' => route('blog.show', $article),
    ];

    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => route('blog.show', $article),
        ],
        'headline' => $article->title,
        'description' => $article->seo_description ?: $article->excerpt,
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at?->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $article->author?->name ?: setting('company_name', 'Zada Karya Production'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => setting('company_name', 'Zada Karya Production'),
            'logo' => setting('logo') ? [
                '@type' => 'ImageObject',
                'url' => asset('storage/'.setting('logo')),
            ] : null,
        ],
    ];

    if ($article->og_image || $article->featured_image) {
        $articleSchema['image'] = asset('storage/'.($article->og_image ?: $article->featured_image));
    }

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $breadcrumbItems,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endsection

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid gap-10 lg:grid-cols-3">

        {{-- Konten artikel --}}
        <article class="min-w-0 lg:col-span-2" data-reveal>
            <nav class="flex flex-wrap items-center text-sm text-neutral-500 gap-1.5" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a>
                <span class="text-neutral-400">/</span>
                <a href="{{ route('blog.index') }}" class="hover:text-brand-600">Blog</a>
                @if($article->category)
                    <span class="text-neutral-400">/</span>
                    <a href="{{ route('blog.index', ['kategori' => $article->category->slug]) }}" class="hover:text-brand-600">{{ $article->category->name }}</a>
                @endif
                <span class="text-neutral-400">/</span>
                <span class="text-neutral-700 font-medium truncate max-w-xs sm:max-w-md">{{ $article->title }}</span>
            </nav>

            <div class="mt-6 flex flex-wrap items-center gap-3 text-sm text-neutral-500">
                @if($article->category)
                    <a href="{{ route('blog.index', ['kategori' => $article->category->slug]) }}" class="rounded-full bg-brand-100 px-3 py-1 font-semibold text-brand-600">{{ $article->category->name }}</a>
                @endif
                <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('d F Y') }}</time>
                @if($article->author)<span>oleh {{ $article->author->name }}</span>@endif
            </div>

            <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">{{ $article->title }}</h1>

            @if($article->excerpt)
                <p class="mt-4 text-lg leading-relaxed text-neutral-600">{{ $article->excerpt }}</p>
            @endif

            @if($article->featured_image)
                <img src="{{ asset('storage/'.$article->featured_image) }}" alt="{{ $article->title }}" class="mt-8 w-full rounded-xl object-cover">
            @endif

            <div class="prose-zada mt-8">{!! $article->content !!}</div>

            @if($article->tags)
                <div class="mt-8 flex flex-wrap gap-2 border-t border-line pt-6">
                    @foreach($article->tags as $tag)
                        <span class="rounded-full border border-line bg-white px-3 py-1 text-xs font-medium text-neutral-600">#{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            {{-- CTA bawah artikel --}}
            <div class="mt-10 rounded-xl bg-brand-600 p-8 text-center">
                <h2 class="text-xl font-bold text-white">Punya kebutuhan konveksi?</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-white/80">Konsultasikan kebutuhan seragam, polo, kaos, atau apparel custom Anda bersama Zada Karya Production.</p>
                <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}"
                   target="_blank" rel="noopener"
                   onclick="if(window.gtag){gtag('event','whatsapp_click');}"
                   class="btn-wa mt-5">Konsultasi via WhatsApp</a>
            </div>
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-9 lg:border-l lg:border-line lg:pl-8" data-reveal-stagger>
            {{-- Pencarian --}}
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-500">Cari Artikel</h2>
                <form method="GET" action="{{ route('blog.index') }}" class="mt-3 flex gap-2">
                    <input type="search" name="q" placeholder="Kata kunci..." class="form-input flex-1">
                    <button type="submit" class="btn-primary !px-4 !py-2.5" aria-label="Cari">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </button>
                </form>
            </div>

            {{-- Kategori --}}
            @if($categories->isNotEmpty())
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-500">Kategori</h2>
                    <ul class="mt-3 space-y-1">
                        @foreach($categories as $category)
                            <li>
                                <a href="{{ route('blog.index', ['kategori' => $category->slug]) }}"
                                   class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition {{ $article->article_category_id === $category->id ? 'bg-brand-50 text-brand-600' : 'text-neutral-600 hover:bg-cream hover:text-ink' }}">
                                    {{ $category->name }}
                                    <span class="rounded-full bg-cream px-2 py-0.5 text-xs text-neutral-500">{{ $category->articles_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Artikel terbaru --}}
            @if($latestArticles->isNotEmpty())
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-500">Artikel Terbaru</h2>
                    <ul class="mt-3 divide-y divide-line">
                        @foreach($latestArticles as $latest)
                            <li>
                                <a href="{{ route('blog.show', $latest) }}" class="group flex gap-3 py-3">
                                    @if($latest->featured_image)
                                        <img src="{{ asset('storage/'.$latest->featured_image) }}" alt="" loading="lazy" class="h-14 w-14 shrink-0 rounded-lg object-cover">
                                    @else
                                        <x-placeholder-image class="h-14 w-14 shrink-0 rounded-lg" />
                                    @endif
                                    <div class="min-w-0">
                                        <p class="line-clamp-2 text-sm font-semibold leading-snug text-ink group-hover:text-brand-600">{{ $latest->title }}</p>
                                        <p class="mt-1 text-xs text-neutral-500">{{ $latest->published_at?->translatedFormat('d M Y') }}</p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- CTA konsultasi --}}
            <div class="rounded-xl bg-cream p-5">
                <h2 class="font-bold text-ink">Butuh jasa konveksi?</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">Seragam, polo shirt, kaos sablon, celana, hingga apparel custom — konsultasi gratis.</p>
                <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}"
                   target="_blank" rel="noopener"
                   onclick="if(window.gtag){gtag('event','whatsapp_click');}"
                   class="btn-wa mt-4 w-full !py-2.5 text-xs">Konsultasi via WhatsApp</a>
                <a href="{{ route('services.index') }}" class="btn-outline mt-2 w-full !py-2.5 text-xs">Lihat Layanan Kami</a>
            </div>
        </aside>
    </div>
</div>

@if($related->isNotEmpty())
<section class="border-t border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink">Artikel Terkait</h2>
        <div class="mt-6 grid gap-5 md:grid-cols-3" data-reveal-stagger>
            @foreach($related as $item)
                <x-article-card :article="$item" />
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
