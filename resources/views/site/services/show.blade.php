@extends('layouts.site')

@section('title', ($service->seo_title ?: $service->name.' — '.setting('company_name')))
@section('meta_description', $service->seo_description ?: $service->short_description)
@if($service->featured_image)
    @section('og_image', asset('storage/'.$service->featured_image))
@endif

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-neutral-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('services.index') }}" class="hover:text-brand-600">Layanan</a>
            <span class="mx-1.5">/</span>
            <span class="text-ink">{{ $service->name }}</span>
        </nav>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid gap-10 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if($service->featured_image)
                <img src="{{ asset('storage/'.$service->featured_image) }}" alt="{{ $service->name }}" class="w-full rounded-xl object-cover">
            @else
                <x-placeholder-image :label="$service->name" class="aspect-[16/9] w-full rounded-xl" />
            @endif

            <h1 class="mt-8 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">{{ $service->name }}</h1>
            <div class="prose-zada mt-4">{!! $service->description !!}</div>

            @if($service->features)
                <h2 class="mt-8 text-xl font-bold text-ink">Yang Anda Dapatkan</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($service->features as $feature)
                        <li class="flex items-start gap-2.5 rounded-lg border border-line bg-white p-3.5 text-sm text-neutral-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-8 grid gap-5 sm:grid-cols-2">
                @if($service->material_info)
                    <div class="rounded-xl border border-line bg-cream p-5">
                        <h3 class="font-bold text-ink">Informasi Bahan</h3>
                        <p class="mt-2 text-sm leading-relaxed text-neutral-600">{{ $service->material_info }}</p>
                    </div>
                @endif
                @if($service->production_info)
                    <div class="rounded-xl border border-line bg-cream p-5">
                        <h3 class="font-bold text-ink">Informasi Produksi</h3>
                        <p class="mt-2 text-sm leading-relaxed text-neutral-600">{{ $service->production_info }}</p>
                    </div>
                @endif
            </div>

            @if($service->faq)
                <h2 class="mt-10 text-xl font-bold text-ink">Pertanyaan Umum</h2>
                <div class="mt-4 space-y-3">
                    @foreach($service->faq as $faq)
                        <details class="group rounded-lg border border-line bg-white p-4">
                            <summary class="cursor-pointer font-semibold text-ink">{{ $faq['q'] ?? '' }}</summary>
                            <p class="mt-2 text-sm text-neutral-600">{{ $faq['a'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="space-y-5">
            <div class="rounded-xl border border-line bg-white p-6">
                <h2 class="font-bold text-ink">Tertarik dengan layanan ini?</h2>
                @if($service->min_order)
                    <p class="mt-3 flex items-center justify-between rounded-lg bg-cream px-4 py-3 text-sm">
                        <span class="text-neutral-600">Minimum Order</span>
                        <span class="font-bold text-ink">{{ $service->min_order }}</span>
                    </p>
                @endif
                <p class="mt-3 text-sm leading-relaxed text-neutral-600">Konsultasikan kebutuhan {{ strtolower($service->name) }} Anda — kami bantu tentukan bahan, model, dan estimasi harga.</p>
                <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai '.$service->name.'.') }}"
                   target="_blank" rel="noopener"
                   onclick="if(window.gtag){gtag('event','whatsapp_click');}"
                   class="btn-wa mt-4 w-full">Konsultasi via WhatsApp</a>
                <a href="{{ route('consultation.create') }}?layanan={{ $service->id }}" class="btn-outline mt-2.5 w-full">Isi Form Konsultasi</a>
            </div>

            @if($otherServices->isNotEmpty())
                <div class="rounded-xl border border-line bg-white p-6">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-neutral-500">Layanan Lainnya</h3>
                    <ul class="mt-3 divide-y divide-line">
                        @foreach($otherServices as $other)
                            <li><a href="{{ route('services.show', $other) }}" class="block py-2.5 text-sm font-medium text-ink hover:text-brand-600">{{ $other->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
