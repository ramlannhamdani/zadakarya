@extends('layouts.site')

@section('title', 'Konsultasi — '.setting('company_name'))
@section('meta_description', 'Konsultasikan kebutuhan konveksi Anda dengan Zada Karya Production. Isi form konsultasi atau hubungi kami langsung via WhatsApp.')

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8" data-reveal>
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Konsultasi</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-ink">Konsultasikan Kebutuhan Anda</h1>
        <p class="mt-4 max-w-2xl text-neutral-600">Ceritakan kebutuhan produksi Anda — tim kami akan menghubungi Anda untuk membahas bahan, model, jumlah, dan estimasi harga.</p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if(session('consultation_success'))
        <div class="mx-auto max-w-2xl rounded-xl border border-green-200 bg-green-50 p-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                <svg class="h-7 w-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </span>
            <h2 class="mt-4 text-2xl font-extrabold text-ink">Konsultasi Terkirim!</h2>
            <p class="mt-3 text-neutral-600">Terima kasih. Tim kami akan segera menghubungi Anda. Agar lebih cepat, lanjutkan percakapan langsung melalui WhatsApp:</p>
            <a href="{{ session('wa_url', wa_link()) }}" target="_blank" rel="noopener"
               onclick="if(window.gtag){gtag('event','whatsapp_click');}"
               class="btn-wa mt-6 !px-8">Lanjutkan ke WhatsApp</a>
            <p class="mt-4"><a href="{{ route('home') }}" class="text-sm font-medium text-neutral-500 hover:text-brand-600">Kembali ke Beranda</a></p>
        </div>
    @else
        <div class="grid gap-10 lg:grid-cols-3" data-reveal-stagger>
            <form method="POST" action="{{ route('consultation.store') }}" enctype="multipart/form-data" class="lg:col-span-2"
                  onsubmit="if(window.gtag){gtag('event','consultation_submit');}">
                @csrf
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                @if($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <p class="font-semibold">Mohon periksa kembali isian Anda:</p>
                        <ul class="mt-1.5 list-disc pl-5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="name">Nama <span class="text-brand-600">*</span></label>
                        <input class="form-input" type="text" id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label class="form-label" for="company">Perusahaan / Organisasi</label>
                        <input class="form-input" type="text" id="company" name="company" value="{{ old('company') }}">
                    </div>
                    <div>
                        <label class="form-label" for="whatsapp">Nomor WhatsApp <span class="text-brand-600">*</span></label>
                        <input class="form-input" type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label class="form-label" for="service_id">Layanan yang Dibutuhkan</label>
                        <select class="form-input" id="service_id" name="service_id">
                            <option value="">— Pilih layanan —</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" @selected(old('service_id', request('layanan')) == $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="estimated_quantity">Estimasi Jumlah</label>
                        <input class="form-input" type="text" id="estimated_quantity" name="estimated_quantity" value="{{ old('estimated_quantity') }}" placeholder="Contoh: 100 pcs">
                    </div>
                    <div>
                        <label class="form-label" for="target_date">Target Selesai</label>
                        <input class="form-input" type="date" id="target_date" name="target_date" value="{{ old('target_date') }}">
                    </div>
                    <div>
                        <label class="form-label" for="attachment">File / Desain (opsional)</label>
                        <input class="form-input !py-2" type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.ai,.psd,.zip">
                        <p class="mt-1 text-xs text-neutral-500">JPG, PNG, PDF, AI, PSD, atau ZIP. Maks 5 MB.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label" for="description">Ceritakan Kebutuhan Anda <span class="text-brand-600">*</span></label>
                        <textarea class="form-input" id="description" name="description" rows="5" required placeholder="Contoh: Kami butuh 150 polo shirt dengan bordir logo untuk seragam karyawan...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-6 w-full sm:w-auto sm:!px-10">Kirim Konsultasi</button>
            </form>

            <aside class="space-y-5">
                <div class="rounded-xl border border-line bg-white p-6">
                    <h2 class="font-bold text-ink">Lebih cepat via WhatsApp</h2>
                    <p class="mt-2 text-sm leading-relaxed text-neutral-600">Ingin jawaban langsung? Chat kami sekarang — konsultasi gratis dan tanpa komitmen.</p>
                    <a href="{{ wa_link('Halo Zada Karya Production, saya ingin berkonsultasi mengenai kebutuhan konveksi.') }}"
                       target="_blank" rel="noopener"
                       onclick="if(window.gtag){gtag('event','whatsapp_click');}"
                       class="btn-wa mt-4 w-full">Chat WhatsApp</a>
                </div>
                <div class="rounded-xl bg-cream p-6">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-neutral-500">Apa yang terjadi setelah ini?</h3>
                    <ol class="mt-3 space-y-3 text-sm text-neutral-600">
                        <li class="flex gap-3"><span class="font-extrabold text-brand-600">1.</span> Tim kami menghubungi Anda via WhatsApp.</li>
                        <li class="flex gap-3"><span class="font-extrabold text-brand-600">2.</span> Diskusi kebutuhan, bahan, dan estimasi harga.</li>
                        <li class="flex gap-3"><span class="font-extrabold text-brand-600">3.</span> Deal — pesanan dibuat dan Anda menerima nomor pesanan.</li>
                        <li class="flex gap-3"><span class="font-extrabold text-brand-600">4.</span> Pantau progress produksi di halaman <a href="{{ route('tracking.index') }}" class="font-semibold text-brand-600 underline">Tracking</a>.</li>
                    </ol>
                </div>
            </aside>
        </div>
    @endif
</section>
@endsection
