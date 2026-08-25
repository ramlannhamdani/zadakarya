@extends('layouts.site')

@section('title', 'Layanan Konveksi — '.setting('company_name'))
@section('meta_description', 'Layanan konveksi Zada Karya Production: seragam kerja, seragam sekolah, seragam kantor, polo shirt, kaos sablon, celana, dan jahit custom.')

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8" data-reveal>
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Layanan</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-ink">Layanan Konveksi Kami</h1>
        <p class="mt-4 max-w-2xl text-neutral-600">Dari seragam hingga apparel custom — semua diproduksi dengan proses terukur dan quality check di setiap tahap.</p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-reveal-stagger>
        @forelse($services as $service)
            <x-service-card :service="$service" />
        @empty
            <p class="col-span-full text-neutral-500">Belum ada layanan yang dipublikasikan.</p>
        @endforelse
    </div>
</section>
@endsection
