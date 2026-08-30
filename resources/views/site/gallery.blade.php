@extends('layouts.site')

@section('title', 'Galeri — '.setting('company_name'))
@section('meta_description', 'Galeri hasil produksi Zada Karya Production: dokumentasi seragam, polo shirt, kaos sablon, jersey, dan garment custom yang pernah kami kerjakan.')

@section('content')
<x-page-hero
    eyebrow="Galeri"
    title="Dokumentasi Produksi
dari Waktu ke Waktu"
    text="Kumpulan foto hasil produksi dan aktivitas workshop kami — dari proses jahit hingga produk siap kirim."
    icon="camera" />

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if($items->isEmpty())
        <div class="rounded-xl border border-line bg-white py-16 text-center">
            <p class="text-neutral-500">Galeri sedang kami siapkan.</p>
            <a href="{{ route('portfolio.index') }}" class="btn-outline mt-5">Lihat Portfolio Kami</a>
        </div>
    @else
        {{-- Masonry: gambar tampil sesuai rasio aslinya, tersusun per kolom --}}
        <div class="columns-2 gap-4 sm:columns-3 lg:columns-4" data-reveal-stagger>
            @foreach($items as $item)
                <div class="group relative mb-4 break-inside-avoid overflow-hidden rounded-xl bg-cream">
                    <img src="{{ asset('storage/'.($item->thumb_path ?: $item->image_path)) }}"
                         alt="Dokumentasi produksi {{ setting('company_name') }}"
                         loading="lazy"
                         class="w-full transition duration-500 ease-out group-hover:scale-[1.05]">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-brand-800/40 via-transparent to-transparent opacity-0 transition duration-500 group-hover:opacity-100"></div>
                </div>
            @endforeach
        </div>

        <div class="mt-10">{{ $items->links() }}</div>
    @endif
</section>

<section class="bg-brand-600">
    <div class="mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 lg:px-8" data-reveal>
        <h2 class="text-2xl font-extrabold text-white sm:text-3xl">Ingin hasil seperti ini untuk kebutuhan Anda?</h2>
        <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}"
           target="_blank" rel="noopener"
           onclick="if(window.gtag){gtag('event','whatsapp_click');}"
           class="btn-wa mt-6">Konsultasi via WhatsApp</a>
    </div>
</section>
@endsection
