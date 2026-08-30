@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
@php
    $tabs = [
        'perusahaan' => 'Perusahaan',
        'branding' => 'Logo & Foto',
        'beranda' => 'Beranda',
        'sosial' => 'Sosial & Footer',
        'seo' => 'SEO & Analytics',
        'tracking' => 'Tracking',
        'invoice' => 'Invoice',
        'akun' => 'Akun',
    ];
    // Field per tab: dipakai untuk membuka tab yang punya error validasi.
    $tabFields = [
        'perusahaan' => ['company_name', 'tagline', 'whatsapp', 'email', 'address', 'city'],
        'branding' => ['logo', 'logo_light', 'favicon', 'workshop_photo_1', 'workshop_photo_2', 'workshop_photo_3'],
        'beranda' => ['hero_image', 'hero_image_style', 'hero_badge', 'hero_title', 'hero_title_accent', 'hero_text', 'hero_rating_text', 'hero_stats'],
        'sosial' => ['instagram', 'facebook', 'tiktok', 'google_maps_url', 'footer_text'],
        'seo' => ['seo_title', 'seo_description', 'analytics_id'],
        'tracking' => ['show_ongoing'],
        'invoice' => ['invoice_company_name', 'invoice_address', 'invoice_bank_info', 'invoice_terms', 'invoice_signer', 'invoice_signature', 'invoice_stamp'],
        'akun' => ['current_password', 'password', 'password_confirmation'],
    ];
    $errorTab = null;
    foreach ($tabFields as $t => $fields) {
        foreach ($fields as $f) {
            if ($errors->has($f) || $errors->has($f.'_pick')) { $errorTab = $t; break 2; }
        }
    }
@endphp

<div class="max-w-3xl"
     x-data="{
        tabs: @js(array_keys($tabs)),
        tab: @js($errorTab ?? 'perusahaan'),
        init() {
            const h = location.hash.slice(1);
            if (! @js((bool) $errorTab) && this.tabs.includes(h)) this.tab = h;
        },
        go(t) { this.tab = t; history.replaceState(null, '', '#' + t); },
        // Validasi manual: field wajib yang kosong bisa berada di tab yang sedang tersembunyi.
        submit(e) {
            const form = e.target;
            form.action = form.action.split('#')[0] + '#' + this.tab; // fragment ikut ke redirect
            if (form.checkValidity()) return;
            e.preventDefault();
            const invalid = form.querySelector(':invalid');
            const panel = invalid?.closest('[data-tab]');
            if (panel) this.go(panel.dataset.tab);
            this.$nextTick(() => invalid?.reportValidity());
        }
     }">

    {{-- Tab bar --}}
    <div class="no-scrollbar flex gap-1 overflow-x-auto border-b border-line pb-px">
        @foreach($tabs as $key => $label)
            <button type="button" @click="go('{{ $key }}')"
                    :class="tab === '{{ $key }}' ? 'border border-b-0 border-line bg-white text-brand-600' : 'text-neutral-500 hover:text-ink'"
                    class="shrink-0 whitespace-nowrap rounded-t-lg px-3 py-2.5 text-sm font-semibold transition">{{ $label }}</button>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-5" novalidate
          x-show="tab !== 'akun'" @submit="submit($event)">
        @csrf @method('PATCH')

        {{-- ===== Perusahaan ===== --}}
        <div data-tab="perusahaan" x-show="tab === 'perusahaan'" x-cloak>
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Identitas Perusahaan</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="company_name">Nama Perusahaan <span class="text-brand-600">*</span></label>
                        <input class="form-input" type="text" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" required>
                    </div>
                    <div>
                        <label class="form-label" for="tagline">Tagline</label>
                        <input class="form-input" type="text" id="tagline" name="tagline" value="{{ old('tagline', $settings['tagline'] ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="whatsapp">WhatsApp <span class="text-brand-600">*</span></label>
                        <input class="form-input" type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input class="form-input" type="email" id="email" name="email" value="{{ old('email', $settings['email'] ?? '') }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label" for="address">Alamat</label>
                        <textarea class="form-input" id="address" name="address" rows="2">{{ old('address', $settings['address'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" for="city">Kota</label>
                        <input class="form-input" type="text" id="city" name="city" value="{{ old('city', $settings['city'] ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Logo & Foto ===== --}}
        <div data-tab="branding" x-show="tab === 'branding'" x-cloak>
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Logo &amp; Favicon</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="logo">Logo (latar terang)</label>
                        <x-admin.media-picker name="logo" :current="$settings['logo'] ?? null">
                            <img src="{{ asset('storage/'.($settings['logo'] ?? '')) }}" alt="Logo" class="h-12 w-auto object-contain">
                        </x-admin.media-picker>
                        <p class="mt-1 text-xs text-neutral-500">Dipakai di navbar. Jika ada, teks nama brand disembunyikan (tetap terbaca SEO).</p>
                    </div>
                    <div>
                        <label class="form-label" for="logo_light">Logo Putih (latar gelap)</label>
                        <x-admin.media-picker name="logo_light" :current="$settings['logo_light'] ?? null">
                            <span class="inline-block rounded-lg bg-brand-800 p-2">
                                <img src="{{ asset('storage/'.($settings['logo_light'] ?? '')) }}" alt="Logo putih" class="h-10 w-auto object-contain">
                            </span>
                        </x-admin.media-picker>
                        <p class="mt-1 text-xs text-neutral-500">Dipakai di footer. Wajib PNG/WebP dengan latar transparan.</p>
                    </div>
                    <div>
                        <label class="form-label" for="favicon">Favicon / Emblem</label>
                        @if(!empty($settings['favicon']))
                            <div x-data="{ removed: false }">
                                <div class="mb-2 flex items-start gap-2" x-show="! removed">
                                    <img src="{{ asset('storage/'.$settings['favicon']) }}" alt="Favicon" class="h-10 w-10 rounded-lg border border-line object-contain">
                                    <button type="button" @click="removed = true" title="Hapus favicon"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-line bg-white text-neutral-500 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span class="sr-only">Hapus favicon</span>
                                    </button>
                                </div>
                                <div class="mb-2 flex flex-wrap items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs" x-show="removed" x-cloak>
                                    <input type="hidden" name="remove_favicon" value="1">
                                    <span class="font-semibold text-red-700">Favicon akan dihapus saat disimpan.</span>
                                    <button type="button" @click="removed = false" class="font-semibold text-neutral-600 underline hover:text-ink">Batalkan</button>
                                </div>
                            </div>
                        @endif
                        <input class="form-input !py-2" type="file" id="favicon" name="favicon" accept=".ico,.png">
                        <p class="mt-1 text-xs text-neutral-500">Ikon tab browser <strong>sekaligus emblem di header PDF invoice</strong>. Gunakan PNG persegi minimal 256&times;256 px (ICO tidak bisa dipakai di PDF), maks 512 KB.</p>
                    </div>
                </div>
            </div>

            <div class="admin-card mt-5">
                <h2 class="font-extrabold text-ink">Foto Workshop (halaman Tentang Kami)</h2>
                <p class="mt-1 text-sm text-neutral-500">Tiga foto pada section "Workshop Kami". Kosong = placeholder default.</p>
                <div class="mt-4 grid gap-5 sm:grid-cols-3">
                    @foreach([1 => 'Cutting', 2 => 'Sewing', 3 => 'QC'] as $i => $label)
                        <div>
                            <label class="form-label" for="workshop_photo_{{ $i }}">Foto {{ $i }} <span class="text-neutral-400">({{ $label }})</span></label>
                            <x-admin.media-picker :name="'workshop_photo_'.$i" :current="$settings['workshop_photo_'.$i] ?? null">
                                <img src="{{ asset('storage/'.($settings['workshop_photo_'.$i] ?? '')) }}" alt="Foto workshop {{ $i }}" class="h-24 w-24 rounded-lg object-cover">
                            </x-admin.media-picker>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Beranda (hero) ===== --}}
        <div data-tab="beranda" x-show="tab === 'beranda'" x-cloak>
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Hero Halaman Depan</h2>
                <p class="mt-1 text-sm text-neutral-500">Kosongkan field mana pun untuk memakai teks bawaan.</p>
                <div class="mt-4 grid gap-5">
                    <div>
                        <label class="form-label" for="hero_image">Foto Hero</label>
                        <x-admin.media-picker name="hero_image" :current="$settings['hero_image'] ?? null">
                            <span class="inline-block rounded-lg bg-brand-600 p-3">
                                <img src="{{ asset('storage/'.($settings['hero_image'] ?? '')) }}" alt="Foto hero" class="h-32 w-auto object-contain">
                            </span>
                        </x-admin.media-picker>
                        <p class="mt-1 text-xs text-neutral-500">Ambil dari <strong>Galeri</strong> atau upload baru, maks 4 MB. Kosong = memakai cover portfolio terbaru, lalu ilustrasi bawaan.</p>
                    </div>
                    <div>
                        <label class="form-label" for="hero_image_style">Gaya Tampilan Foto</label>
                        <select class="form-input" id="hero_image_style" name="hero_image_style">
                            <option value="framed" @selected(($settings['hero_image_style'] ?? 'framed') !== 'cutout')>Foto dibingkai — foto produk biasa, tampil miring di atas bidang maroon</option>
                            <option value="cutout" @selected(($settings['hero_image_style'] ?? '') === 'cutout')>Potongan model — PNG latar transparan, berdiri di atas bidang maroon</option>
                        </select>
                        <p class="mt-1 text-xs text-neutral-500">Terisi otomatis saat memilih gambar baru (terdeteksi dari transparansi); ubah di sini kalau hasilnya kurang pas.</p>
                    </div>
                    <div>
                        <label class="form-label" for="hero_badge">Badge (label kecil di atas judul)</label>
                        <input class="form-input" type="text" id="hero_badge" name="hero_badge" value="{{ old('hero_badge', $settings['hero_badge'] ?? '') }}" placeholder="{{ \App\Support\HeroDefaults::BADGE }}">
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="form-label" for="hero_title">Judul (hitam, Enter = baris baru)</label>
                            <textarea class="form-input" id="hero_title" name="hero_title" rows="2" placeholder="Buat Gayamu,&#10;Kami Wujudkan">{{ old('hero_title', $settings['hero_title'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="form-label" for="hero_title_accent">Judul baris terakhir (maroon)</label>
                            <input class="form-input" type="text" id="hero_title_accent" name="hero_title_accent" value="{{ old('hero_title_accent', $settings['hero_title_accent'] ?? '') }}" placeholder="{{ \App\Support\HeroDefaults::TITLE_ACCENT }}">
                            <p class="mt-1 text-xs text-neutral-500">Sebaiknya ringkas agar tetap muat satu baris.</p>
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="hero_text">Paragraf</label>
                        <textarea class="form-input" id="hero_text" name="hero_text" rows="3" placeholder="{{ \App\Support\HeroDefaults::TEXT }}">{{ old('hero_text', $settings['hero_text'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" for="hero_rating_text">Teks rating</label>
                        <input class="form-input" type="text" id="hero_rating_text" name="hero_rating_text" value="{{ old('hero_rating_text', $settings['hero_rating_text'] ?? '') }}" placeholder="{{ \App\Support\HeroDefaults::RATING_TEXT }}">
                        <p class="mt-1 text-xs text-neutral-500">Kosongkan untuk menghitung otomatis dari Ulasan Google (minimal 5 ulasan tampil).</p>
                    </div>
                    <div>
                        <label class="form-label" for="hero_stats">Statistik (satu baris per item, format <code class="rounded bg-cream px-1">Nilai | Label</code>)</label>
                        <textarea class="form-input" id="hero_stats" name="hero_stats" rows="5" placeholder="{{ \App\Support\HeroDefaults::STATS }}">{{ old('hero_stats', $settings['hero_stats'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-neutral-500">Maksimal 5 baris. Ikon mengikuti urutan: orang, kaos, medali, truk, headset.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Sosial & Footer ===== --}}
        <div data-tab="sosial" x-show="tab === 'sosial'" x-cloak>
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Sosial Media &amp; Footer</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-3">
                    <div>
                        <label class="form-label" for="instagram">Instagram (URL)</label>
                        <input class="form-input" type="text" id="instagram" name="instagram" value="{{ old('instagram', $settings['instagram'] ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="facebook">Facebook (URL)</label>
                        <input class="form-input" type="text" id="facebook" name="facebook" value="{{ old('facebook', $settings['facebook'] ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="tiktok">TikTok (URL)</label>
                        <input class="form-input" type="text" id="tiktok" name="tiktok" value="{{ old('tiktok', $settings['tiktok'] ?? '') }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="form-label" for="google_maps_url">Link Google Maps (untuk section ulasan)</label>
                        <input class="form-input" type="url" id="google_maps_url" name="google_maps_url" value="{{ old('google_maps_url', $settings['google_maps_url'] ?? '') }}" placeholder="https://maps.app.goo.gl/...">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="form-label" for="footer_text">Teks Footer</label>
                        <textarea class="form-input" id="footer_text" name="footer_text" rows="2">{{ old('footer_text', $settings['footer_text'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== SEO & Analytics ===== --}}
        <div data-tab="seo" x-show="tab === 'seo'" x-cloak>
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">SEO Default &amp; Analytics</h2>
                <div class="mt-4 grid gap-5">
                    <div>
                        <label class="form-label" for="seo_title">SEO Title Default</label>
                        <input class="form-input" type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $settings['seo_title'] ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="seo_description">SEO Description Default</label>
                        <textarea class="form-input" id="seo_description" name="seo_description" rows="2">{{ old('seo_description', $settings['seo_description'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" for="analytics_id">Google Analytics ID</label>
                        <input class="form-input" type="text" id="analytics_id" name="analytics_id" value="{{ old('analytics_id', $settings['analytics_id'] ?? '') }}" placeholder="G-XXXXXXXXXX">
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Tracking ===== --}}
        <div data-tab="tracking" x-show="tab === 'tracking'" x-cloak>
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Halaman Tracking</h2>
                <label class="mt-4 flex items-start gap-2.5 text-sm font-medium">
                    <input type="hidden" name="show_ongoing" value="0">
                    <input type="checkbox" name="show_ongoing" value="1" @checked(($settings['show_ongoing'] ?? '1') === '1') class="mt-0.5 rounded border-line text-brand-600 focus:ring-brand-600">
                    <span>
                        Tampilkan daftar "Sedang Kami Kerjakan" di halaman tracking
                        <span class="block text-xs font-normal text-neutral-500">Daftar publik pesanan aktif: nama produk, progress tahap, bulan pesan, dan deadline. Tanpa nomor pesanan, tanggal lengkap, nama customer, maupun nama proyek.</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- ===== Invoice ===== --}}
        <div data-tab="invoice" x-show="tab === 'invoice'" x-cloak>
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Informasi Invoice</h2>
                <p class="mt-1 text-sm text-neutral-500">Emblem di header PDF diambil dari <strong>Favicon</strong> (tab Logo &amp; Foto).</p>
                <div class="mt-4 grid gap-5">
                    <div>
                        <label class="form-label" for="invoice_company_name">Nama Perusahaan di Invoice</label>
                        <input class="form-input" type="text" id="invoice_company_name" name="invoice_company_name" value="{{ old('invoice_company_name', $settings['invoice_company_name'] ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="invoice_address">Alamat di Invoice</label>
                        <textarea class="form-input" id="invoice_address" name="invoice_address" rows="2">{{ old('invoice_address', $settings['invoice_address'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" for="invoice_bank_info">Informasi Rekening (tampil di invoice)</label>
                        <textarea class="form-input" id="invoice_bank_info" name="invoice_bank_info" rows="3" placeholder="Bank : BCA&#10;Account Name : Nama Pemilik&#10;Account No. : 1234567890">{{ old('invoice_bank_info', $settings['invoice_bank_info'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-neutral-500">Satu baris per keterangan, format <code class="rounded bg-cream px-1">Label : Nilai</code> agar rapi di PDF.</p>
                    </div>
                    <div>
                        <label class="form-label" for="invoice_terms">Catatan / Ketentuan (bagian bawah invoice)</label>
                        <textarea class="form-input" id="invoice_terms" name="invoice_terms" rows="3" placeholder="Barang yang sudah dipesan tidak bisa dibatalkan&#10;Pelunasan wajib dilakukan sebelum pengambilan barang">{{ old('invoice_terms', $settings['invoice_terms'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-neutral-500">Satu baris = satu poin. Kosongkan untuk memakai ketentuan bawaan.</p>
                    </div>
                    <div>
                        <label class="form-label" for="invoice_signer">Nama Penandatangan ("Hormat kami")</label>
                        <input class="form-input" type="text" id="invoice_signer" name="invoice_signer" value="{{ old('invoice_signer', $settings['invoice_signer'] ?? '') }}" placeholder="Contoh: Hilmi Rifai — Zada Karya Production">
                        <p class="mt-1 text-xs text-neutral-500">Tampil di bawah garis tanda tangan. Kosong = nama perusahaan.</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Gambar Tanda Tangan (opsional)</label>
                            <x-admin.media-picker name="invoice_signature" :current="$settings['invoice_signature'] ?? null">
                                <span class="inline-flex h-20 items-center rounded-lg bg-cream px-4">
                                    <img src="{{ asset('storage/'.($settings['invoice_signature'] ?? '')) }}" alt="Tanda tangan" class="max-h-16 w-auto object-contain">
                                </span>
                            </x-admin.media-picker>
                            <p class="mt-1 text-xs text-neutral-500">PNG/WebP latar transparan. Dipasang di kolom "Hormat kami" pada <strong>setiap</strong> PDF invoice.</p>
                        </div>
                        <div>
                            <label class="form-label">Stempel LUNAS (opsional)</label>
                            <x-admin.media-picker name="invoice_stamp" :current="$settings['invoice_stamp'] ?? null">
                                <span class="inline-flex h-20 items-center rounded-lg bg-cream px-4">
                                    <img src="{{ asset('storage/'.($settings['invoice_stamp'] ?? '')) }}" alt="Stempel lunas" class="max-h-16 w-auto object-contain">
                                </span>
                            </x-admin.media-picker>
                            <p class="mt-1 text-xs text-neutral-500">PNG/WebP latar transparan, sebaiknya persegi. Dicetak otomatis di antara kolom tanda tangan <strong>hanya saat pesanan berstatus Lunas</strong>, menggantikan watermark "LUNAS".</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary mt-6">Simpan Pengaturan</button>
    </form>

    {{-- ===== Akun Admin (form terpisah dari form pengaturan) ===== --}}
    <form method="POST" action="{{ route('admin.password.update') }}" class="admin-card mt-5" data-tab="akun"
          x-show="tab === 'akun'" x-cloak @submit="$el.action = $el.action.split('#')[0] + '#akun'">
        @csrf @method('PATCH')
        <h2 class="font-extrabold text-ink">Akun Admin — Ganti Password</h2>
        <p class="mt-1 text-sm text-neutral-500">Login sebagai <span class="font-semibold">{{ auth()->user()->email }}</span>. Minimal 10 karakter, mengandung huruf dan angka. Sesi di perangkat lain akan diputus.</p>
        <div class="mt-4 grid gap-5 sm:grid-cols-3">
            <div>
                <label class="form-label" for="current_password">Password Saat Ini</label>
                <input class="form-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
            </div>
            <div>
                <label class="form-label" for="password">Password Baru</label>
                <input class="form-input" type="password" id="password" name="password" autocomplete="new-password" required>
            </div>
            <div>
                <label class="form-label" for="password_confirmation">Ulangi Password Baru</label>
                <input class="form-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>
        </div>
        <button type="submit" class="btn-primary mt-5">Ganti Password</button>
    </form>
</div>
@endsection
