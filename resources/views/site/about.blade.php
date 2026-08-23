@extends('layouts.site')

@section('title', 'Tentang Kami — '.setting('company_name'))
@section('meta_description', 'Mengenal Zada Karya Production — perusahaan jasa konveksi yang menyediakan kebutuhan produksi apparel dan garment custom.')

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Tentang Kami</p>
        <h1 class="mt-2 max-w-3xl text-4xl font-extrabold tracking-tight text-ink">Partner Produksi Konveksi yang Bisa Anda Andalkan</h1>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="grid gap-12 lg:grid-cols-2">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-ink">Cerita Kami</h2>
            <div class="prose-zada mt-4">
                <p><strong>Zada Karya Production</strong> adalah perusahaan jasa konveksi yang menyediakan kebutuhan produksi apparel dan garment custom — mulai dari seragam kerja, seragam sekolah, seragam kantor, seragam olahraga, polo shirt, kaos sablon, celana, hingga jahit custom sesuai kebutuhan Anda.</p>
                <p>Kami percaya hasil produksi yang baik lahir dari proses yang terukur. Karena itu setiap pesanan di Zada Karya Production berjalan melalui tujuh tahap yang jelas — dari pesanan diterima hingga selesai — dan Anda dapat memantau progresnya kapan saja melalui halaman tracking dengan nomor pesanan Anda.</p>
                <p>Setiap tahap produksi diawasi dan melewati quality check sebelum produk dikemas dan dikirim, sehingga kualitas tetap konsisten di setiap pesanan.</p>
            </div>
        </div>
        <div class="space-y-5">
            <div class="rounded-xl border border-line bg-white p-6">
                <h3 class="flex items-center gap-2 font-bold text-ink">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                    Visi
                </h3>
                <p class="mt-3 text-sm leading-relaxed text-neutral-600">Menjadi partner produksi konveksi terpercaya yang mengutamakan kualitas, ketepatan waktu, dan transparansi proses untuk setiap pelanggan.</p>
            </div>
            <div class="rounded-xl border border-line bg-white p-6">
                <h3 class="flex items-center gap-2 font-bold text-ink">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg></span>
                    Misi
                </h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-neutral-600">
                    <li class="flex gap-2"><span class="text-brand-600">&bull;</span> Memberikan hasil produksi berkualitas dengan proses yang terukur.</li>
                    <li class="flex gap-2"><span class="text-brand-600">&bull;</span> Membantu pelanggan menentukan bahan dan model paling tepat melalui konsultasi.</li>
                    <li class="flex gap-2"><span class="text-brand-600">&bull;</span> Menjaga transparansi progress produksi melalui sistem tracking pesanan.</li>
                    <li class="flex gap-2"><span class="text-brand-600">&bull;</span> Menjalankan quality check pada setiap pesanan sebelum pengiriman.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['title' => 'Konsultatif', 'desc' => 'Kami mendengar kebutuhan Anda sebelum menyarankan solusi.'],
            ['title' => 'Transparan', 'desc' => 'Progress produksi dapat dilacak melalui nomor pesanan.'],
            ['title' => 'Terukur', 'desc' => 'Tujuh tahap produksi yang jelas untuk setiap pesanan.'],
            ['title' => 'Berkualitas', 'desc' => 'Quality check sebelum setiap pesanan dikirim.'],
        ] as $value)
            <div class="rounded-xl bg-cream p-6">
                <h3 class="font-bold text-brand-600">{{ $value['title'] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-neutral-600">{{ $value['desc'] }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="border-t border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-ink">Workshop Kami</h2>
                <p class="mt-4 leading-relaxed text-neutral-600">Workshop kami berlokasi di {{ setting('address') }}. Anda dapat berkunjung untuk melihat contoh bahan dan hasil produksi — silakan buat janji terlebih dahulu melalui WhatsApp.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ wa_link('Halo Zada Karya Production, saya ingin membuat janji kunjungan ke workshop.') }}" target="_blank" rel="noopener" class="btn-wa">Hubungi via WhatsApp</a>
                    <a href="{{ route('contact') }}" class="btn-outline">Info Kontak</a>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <x-placeholder-image label="Cutting" class="aspect-square rounded-xl" />
                <x-placeholder-image label="Sewing" class="aspect-square rounded-xl" />
                <x-placeholder-image label="QC" class="aspect-square rounded-xl" />
            </div>
        </div>
    </div>
</section>

<section class="bg-brand-600">
    <div class="mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-white sm:text-3xl">Siap memulai produksi bersama kami?</h2>
        <a href="{{ route('consultation.create') }}" class="btn-wa mt-6">Konsultasi Sekarang</a>
    </div>
</section>
@endsection
