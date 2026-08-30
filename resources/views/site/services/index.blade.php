@extends('layouts.site')

@section('title', 'Layanan Konveksi — '.setting('company_name'))
@section('meta_description', 'Layanan konveksi Zada Karya Production: seragam kerja, seragam sekolah, seragam kantor, polo shirt, kaos sablon, celana, dan jahit custom.')

@section('content')
<x-page-hero
    eyebrow="Layanan"
    title="Solusi Konveksi untuk
Berbagai Kebutuhan"
    text="Dari pakaian personal hingga kebutuhan bisnis dan komunitas, kami siap membantu mewujudkan produk dengan kualitas terbaik."
    icon="shirt" />

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
