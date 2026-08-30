@extends('layouts.site')

@section('title', 'Kontak — '.setting('company_name'))
@section('meta_description', 'Hubungi Zada Karya Production untuk konsultasi kebutuhan konveksi Anda — via WhatsApp, email, atau kunjungi workshop kami.')

@section('content')
<x-page-hero
    eyebrow="Hubungi Kami"
    title="Mari Wujudkan Produk Anda
Bersama Kami"
    text="Punya pertanyaan atau ingin berkonsultasi mengenai kebutuhan konveksi? Tim kami siap membantu."
    icon="headset" />

<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="grid gap-5 md:grid-cols-3" data-reveal-stagger>
        <div class="rounded-xl border border-line bg-white p-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
            </span>
            <h2 class="mt-4 font-bold text-ink">Alamat Workshop</h2>
            <p class="mt-2 text-sm leading-relaxed text-neutral-600">{{ setting('address') }}</p>
        </div>
        <div class="rounded-xl border border-line bg-white p-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#25D366] text-white">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </span>
            <h2 class="mt-4 font-bold text-ink">WhatsApp</h2>
            <p class="mt-2 text-sm text-neutral-600">{{ setting('whatsapp') }}</p>
            <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}" target="_blank" rel="noopener" class="mt-3 inline-block text-sm font-semibold text-brand-600 hover:underline">Chat Sekarang &rarr;</a>
        </div>
        <div class="rounded-xl border border-line bg-white p-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-warm-500 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            </span>
            <h2 class="mt-4 font-bold text-ink">Email</h2>
            <p class="mt-2 text-sm text-neutral-600">{{ setting('email') }}</p>
            <a href="mailto:{{ setting('email') }}" class="mt-3 inline-block text-sm font-semibold text-brand-600 hover:underline">Kirim Email &rarr;</a>
        </div>
    </div>

    @if(setting('instagram') || setting('facebook') || setting('tiktok'))
        <div class="mt-8 flex items-center justify-center gap-4">
            <span class="text-sm font-semibold text-neutral-500">Ikuti kami:</span>
            <x-social-links link-class="text-neutral-500 hover:text-brand-600" />
        </div>
    @endif

    <div class="mt-10 rounded-xl bg-cream p-8 text-center" data-reveal>
        <h2 class="text-xl font-bold text-ink">Lebih suka mengisi form?</h2>
        <p class="mt-2 text-neutral-600">Isi form konsultasi dan tim kami akan menghubungi Anda.</p>
        <a href="{{ route('consultation.create') }}" class="btn-primary mt-5">Isi Form Konsultasi</a>
    </div>
</section>
@endsection
