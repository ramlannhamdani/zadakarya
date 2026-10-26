@extends('layouts.admin')

@section('title', 'Pengaturan Sistem & Konfigurasi Website')

@section('header-actions')
<button type="submit" form="settingsForm" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-regular fa-floppy-disk text-[11px]"></i>
    <span>Simpan Perubahan</span>
</button>
@endsection

@section('content')
@php
    $tabFields = [
        'perusahaan' => ['company_name', 'tagline', 'whatsapp', 'whatsapp_2', 'email', 'address', 'city'],
        'branding' => ['logo', 'logo_light', 'favicon', 'workshop_photo_1', 'workshop_photo_2', 'workshop_photo_3'],
        'beranda' => ['hero_image', 'hero_image_style', 'hero_badge', 'hero_title', 'hero_title_accent', 'hero_text', 'hero_rating_text', 'hero_stats'],
        'sosial' => ['instagram', 'facebook', 'tiktok', 'youtube', 'google_maps_url', 'footer_text'],
        'seo' => ['seo_title', 'seo_description', 'analytics_id', 'google_site_verification'],
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

<div x-data="{
    tab: @js($errorTab ?? 'perusahaan'),
    init() {
        const h = location.hash.slice(1);
        const valid = ['perusahaan', 'branding', 'beranda', 'sosial', 'tracking', 'seo', 'invoice', 'akun'];
        if (! @js((bool) $errorTab) && valid.includes(h)) this.tab = h;
    },
    go(t) {
        this.tab = t;
        history.replaceState(null, '', '#' + t);
    },
    submit(e) {
        const form = e.target;
        form.action = form.action.split('#')[0] + '#' + this.tab;
        if (form.checkValidity()) return;
        e.preventDefault();
        const invalid = form.querySelector(':invalid');
        const panel = invalid?.closest('[data-tab]');
        if (panel) this.go(panel.dataset.tab);
        this.$nextTick(() => invalid?.reportValidity());
    }
}">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT: VERTICAL SETTINGS MENU (3 COLS) -->
        <div class="lg:col-span-4 xl:col-span-3 space-y-3 sticky top-24">
            
            <div class="bg-white rounded-2xl p-3 shadow-sm space-y-1">
                <!-- Group 1 -->
                <div class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    Profil & Identitas
                </div>

                <button type="button" @click="go('perusahaan')"
                        :class="tab === 'perusahaan' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-solid fa-building w-4 text-center" :class="tab === 'perusahaan' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>Perusahaan</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'perusahaan' ? 'opacity-80' : 'text-slate-400'">Identitas & Kontak</span>
                    </div>
                </button>

                <button type="button" @click="go('branding')"
                        :class="tab === 'branding' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-regular fa-image w-4 text-center" :class="tab === 'branding' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>Logo & Foto</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'branding' ? 'opacity-80' : 'text-slate-400'">Favicon, Logo & Workshop</span>
                    </div>
                </button>

                <!-- Group 2 -->
                <div class="pt-3 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    Tampilan Website
                </div>

                <button type="button" @click="go('beranda')"
                        :class="tab === 'beranda' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-solid fa-house w-4 text-center" :class="tab === 'beranda' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>Beranda</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'beranda' ? 'opacity-80' : 'text-slate-400'">Hero, Tagline & Statistik</span>
                    </div>
                </button>

                <button type="button" @click="go('sosial')"
                        :class="tab === 'sosial' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-solid fa-share-nodes w-4 text-center" :class="tab === 'sosial' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>Sosial & Footer</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'sosial' ? 'opacity-80' : 'text-slate-400'">Sosmed, Maps & Copyright</span>
                    </div>
                </button>

                <button type="button" @click="go('tracking')"
                        :class="tab === 'tracking' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-solid fa-truck-fast w-4 text-center" :class="tab === 'tracking' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>Tracking Order</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'tracking' ? 'opacity-80' : 'text-slate-400'">Pencarian Progress Publik</span>
                    </div>
                </button>

                <!-- Group 3 -->
                <div class="pt-3 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    Sistem & Penagihan
                </div>

                <button type="button" @click="go('seo')"
                        :class="tab === 'seo' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-solid fa-chart-line w-4 text-center" :class="tab === 'seo' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>SEO & Analytics</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'seo' ? 'opacity-80' : 'text-slate-400'">Meta Tag & Google Analytics</span>
                    </div>
                </button>

                <button type="button" @click="go('invoice')"
                        :class="tab === 'invoice' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-solid fa-receipt w-4 text-center" :class="tab === 'invoice' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>Template Invoice</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'invoice' ? 'opacity-80' : 'text-slate-400'">Rekening, TTD & Stempel</span>
                    </div>
                </button>

                <button type="button" @click="go('akun')"
                        :class="tab === 'akun' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left">
                    <i class="fa-solid fa-shield-halved w-4 text-center" :class="tab === 'akun' ? 'text-slate-300' : 'text-slate-400'"></i>
                    <div class="truncate">
                        <span>Akun & Keamanan</span>
                        <span class="text-[10px] block font-normal" :class="tab === 'akun' ? 'opacity-80' : 'text-slate-400'">Ganti Password Admin</span>
                    </div>
                </button>

            </div>

            <!-- Quick Info Hint -->
            <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm text-xs text-slate-500 leading-relaxed">
                <i class="fa-solid fa-circle-check text-emerald-500 mr-1.5"></i>
                Seluruh perubahan disimpan ke sistem dan otomatis diterapkan ke website publik.
            </div>

        </div>

        <!-- RIGHT: SETTINGS FORM PANEL (9 COLS) -->
        <div class="lg:col-span-8 xl:col-span-9">

            <form id="settingsForm" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" novalidate
                  x-show="tab !== 'akun'" @submit="submit($event)">
                @csrf @method('PATCH')

                <!-- ========================================== -->
                <!-- 1. TAB: PERUSAHAAN -->
                <!-- ========================================== -->
                <section data-tab="perusahaan" x-show="tab === 'perusahaan'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                    <div class="pb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Identitas Profil Usaha</span>
                        <h3 class="text-base font-bold text-slate-900 mt-0.5">Identitas Perusahaan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Informasi legalitas dan kontak resmi yang tampil di header dan kontak pemesanan.</p>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="company_name">Nama Perusahaan <span class="text-rose-500">*</span></label>
                                <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" required class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="tagline">Tagline</label>
                                <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $settings['tagline'] ?? '') }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="whatsapp">WhatsApp Admin 1 <span class="text-rose-500">*</span></label>
                                <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" required class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="whatsapp_2">WhatsApp Admin 2 (Biolink)</label>
                                <input type="text" id="whatsapp_2" name="whatsapp_2" value="{{ old('whatsapp_2', $settings['whatsapp_2'] ?? '') }}" placeholder="+62 813-..." class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="email">Email Resmi</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $settings['email'] ?? '') }}" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="address">Alamat Workshop / Kantor</label>
                            <textarea id="address" name="address" rows="2.5" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 leading-relaxed">{{ old('address', $settings['address'] ?? '') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="city">Kota / Kabupaten</label>
                                <input type="text" id="city" name="city" value="{{ old('city', $settings['city'] ?? '') }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                            Simpan Pengaturan
                        </button>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 2. TAB: LOGO & FOTO -->
                <!-- ========================================== -->
                <section data-tab="branding" x-show="tab === 'branding'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                    <div class="pb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aset Visual Brand</span>
                        <h3 class="text-base font-bold text-slate-900 mt-0.5">Logo & Favicon</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Kelola logo navbar, favicon browser, dan foto workshop untuk profil perusahaan.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
                        <!-- Logo Latar Terang -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-3">
                            <span class="font-bold text-slate-800 block">Logo (Latar Terang)</span>
                            <x-admin.media-picker name="logo" :current="$settings['logo'] ?? null">
                                <img src="{{ asset('storage/'.($settings['logo'] ?? '')) }}" alt="Logo" class="h-12 w-auto object-contain">
                            </x-admin.media-picker>
                            <p class="text-[10px] text-slate-400 leading-tight">Dipakai di navbar atas. Jika ada, nama brand disembunyikan (tetap terbaca SEO).</p>
                        </div>

                        <!-- Logo Putih Latar Gelap -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-3">
                            <span class="font-bold text-slate-800 block">Logo Putih (Latar Gelap)</span>
                            <x-admin.media-picker name="logo_light" :current="$settings['logo_light'] ?? null">
                                <span class="inline-block rounded-lg bg-slate-900 p-2">
                                    <img src="{{ asset('storage/'.($settings['logo_light'] ?? '')) }}" alt="Logo putih" class="h-10 w-auto object-contain">
                                </span>
                            </x-admin.media-picker>
                            <p class="text-[10px] text-slate-400 leading-tight">Dipakai di footer bawah. Wajib format PNG/WebP transparan.</p>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-3 text-xs max-w-md">
                        <span class="font-bold text-slate-800 block">Favicon / Emblem</span>
                        @if(!empty($settings['favicon']))
                            <div x-data="{ removed: false }">
                                <div class="mb-2 flex items-start gap-2" x-show="! removed">
                                    <img src="{{ asset('storage/'.$settings['favicon']) }}" alt="Favicon" class="h-10 w-10 rounded-lg border border-slate-200 bg-white object-contain">
                                    <button type="button" @click="removed = true" title="Hapus favicon"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>
                                <template x-if="removed">
                                    <div class="mb-2 flex flex-wrap items-center gap-2 rounded-lg bg-rose-50 px-3 py-2 text-xs">
                                        <input type="hidden" name="remove_favicon" value="1">
                                        <span class="font-semibold text-rose-700">Favicon akan dihapus saat disimpan.</span>
                                        <button type="button" @click="removed = false" class="font-semibold text-slate-600 underline">Batalkan</button>
                                    </div>
                                </template>
                            </div>
                        @endif
                        <input class="w-full bg-white text-xs rounded-xl px-3.5 py-2 text-slate-700 border border-slate-200/60 focus:outline-none" type="file" id="favicon" name="favicon" accept=".ico,.png">
                        <p class="text-[10px] text-slate-400">Ikon tab browser <strong class="text-slate-600">sekaligus emblem di header PDF invoice</strong>. PNG minimal 256x256 px.</p>
                    </div>

                    <!-- Foto Workshop -->
                    <div class="pt-4 border-t border-slate-100 space-y-3">
                        <div>
                            <h4 class="font-bold text-slate-900 text-xs">Foto Workshop (Halaman Tentang Kami)</h4>
                            <p class="text-[11px] text-slate-400">Tiga foto pada section "Workshop Kami". Kosong = placeholder default.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                            @foreach([1 => 'Cutting', 2 => 'Sewing', 3 => 'QC'] as $i => $label)
                                <div class="p-3 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-2">
                                    <span class="font-semibold text-slate-700 text-[11px]">Foto {{ $i }} ({{ $label }})</span>
                                    <x-admin.media-picker :name="'workshop_photo_'.$i" :current="$settings['workshop_photo_'.$i] ?? null">
                                        <img src="{{ asset('storage/'.($settings['workshop_photo_'.$i] ?? '')) }}" alt="Foto workshop {{ $i }}" class="h-24 w-full rounded-xl object-cover">
                                    </x-admin.media-picker>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                            Simpan Pengaturan
                        </button>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 3. TAB: BERANDA -->
                <!-- ========================================== -->
                <section data-tab="beranda" x-show="tab === 'beranda'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                    <div class="pb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Halaman Utama</span>
                        <h3 class="text-base font-bold text-slate-900 mt-0.5">Hero Halaman Depan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Atur gambar banner model, headline teks, ulasan rating, dan 5 angka statistik beranda.</p>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-3">
                            <span class="font-semibold text-slate-700 block">Foto Hero Model Banner</span>
                            <x-admin.media-picker name="hero_image" :current="$settings['hero_image'] ?? null">
                                <span class="inline-block rounded-xl bg-brand-900 p-3">
                                    <img src="{{ asset('storage/'.($settings['hero_image'] ?? '')) }}" alt="Foto hero" class="h-24 w-auto object-contain">
                                </span>
                            </x-admin.media-picker>
                            <p class="text-[10px] text-slate-400">Ambil dari Galeri atau upload baru, maks 4 MB. Kosong = memakai ilustrasi bawaan.</p>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="hero_image_style">Gaya Tampilan Foto</label>
                            <select id="hero_image_style" name="hero_image_style" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 font-medium">
                                <option value="framed" @selected(($settings['hero_image_style'] ?? 'framed') !== 'cutout')>Foto dibingkai — foto produk biasa, tampil miring di atas bidang maroon</option>
                                <option value="cutout" @selected(($settings['hero_image_style'] ?? '') === 'cutout')>Potongan model — PNG latar transparan, berdiri di atas bidang maroon</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="hero_badge">Badge (Label kecil di atas judul)</label>
                            <input type="text" id="hero_badge" name="hero_badge" value="{{ old('hero_badge', $settings['hero_badge'] ?? '') }}" placeholder="{{ \App\Support\HeroDefaults::BADGE }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="hero_title">Judul Utama (Hitam, Enter = baris baru)</label>
                                <textarea id="hero_title" name="hero_title" rows="2" placeholder="Buat Gayamu,&#10;Kami Wujudkan" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">{{ old('hero_title', $settings['hero_title'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="hero_title_accent">Judul Baris Terakhir (Maroon)</label>
                                <input type="text" id="hero_title_accent" name="hero_title_accent" value="{{ old('hero_title_accent', $settings['hero_title_accent'] ?? '') }}" placeholder="{{ \App\Support\HeroDefaults::TITLE_ACCENT }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                                <span class="text-[10px] text-slate-400 mt-1 block">Sebaiknya ringkas agar tetap muat satu baris.</span>
                            </div>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="hero_text">Paragraf Pengantar</label>
                            <textarea id="hero_text" name="hero_text" rows="3" placeholder="{{ \App\Support\HeroDefaults::TEXT }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 leading-relaxed">{{ old('hero_text', $settings['hero_text'] ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="hero_rating_text">Teks Rating</label>
                            <input type="text" id="hero_rating_text" name="hero_rating_text" value="{{ old('hero_rating_text', $settings['hero_rating_text'] ?? '') }}" placeholder="{{ \App\Support\HeroDefaults::RATING_TEXT }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan untuk menghitung otomatis dari Ulasan Google (minimal 5 ulasan tampil).</span>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="hero_stats">Statistik (Satu baris per item, format <code class="font-mono text-slate-600 bg-slate-100 px-1 py-0.5 rounded">Nilai | Label</code>)</label>
                            <textarea id="hero_stats" name="hero_stats" rows="5" placeholder="{{ \App\Support\HeroDefaults::STATS }}" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 leading-relaxed">{{ old('hero_stats', $settings['hero_stats'] ?? '') }}</textarea>
                            <span class="text-[10px] text-slate-400 mt-1 block">Maksimal 5 baris. Ikon mengikuti urutan: orang, kaos, medali, truk, headset.</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                            Simpan Pengaturan
                        </button>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 4. TAB: SOSIAL & FOOTER -->
                <!-- ========================================== -->
                <section data-tab="sosial" x-show="tab === 'sosial'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                    <div class="pb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Footer & Media Sosial</span>
                        <h3 class="text-base font-bold text-slate-900 mt-0.5">Sosial Media & Footer</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Koneksikan saluran media sosial, link Google Maps workshop, dan deskripsi footer website.</p>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="instagram">Instagram (URL)</label>
                                <input type="text" id="instagram" name="instagram" value="{{ old('instagram', $settings['instagram'] ?? '') }}" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="facebook">Facebook (URL)</label>
                                <input type="text" id="facebook" name="facebook" value="{{ old('facebook', $settings['facebook'] ?? '') }}" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="tiktok">TikTok (URL)</label>
                                <input type="text" id="tiktok" name="tiktok" value="{{ old('tiktok', $settings['tiktok'] ?? '') }}" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="youtube">YouTube (URL)</label>
                                <input type="text" id="youtube" name="youtube" value="{{ old('youtube', $settings['youtube'] ?? '') }}" placeholder="https://youtube.com/@..." class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="google_maps_url">Link Google Maps (untuk section ulasan & biolink)</label>
                            <input type="url" id="google_maps_url" name="google_maps_url" value="{{ old('google_maps_url', $settings['google_maps_url'] ?? '') }}" placeholder="https://maps.app.goo.gl/..." class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="footer_text">Teks Deskripsi Footer</label>
                            <textarea id="footer_text" name="footer_text" rows="2.5" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 leading-relaxed">{{ old('footer_text', $settings['footer_text'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                            Simpan Pengaturan
                        </button>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 5. TAB: TRACKING -->
                <!-- ========================================== -->
                <section data-tab="tracking" x-show="tab === 'tracking'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                    <div class="pb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tracking Produksi Publik</span>
                        <h3 class="text-base font-bold text-slate-900 mt-0.5">Halaman Tracking Pesanan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Atur keterbukaan data live progress pekerjaan konveksi untuk calon pembeli.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 flex items-start gap-3">
                        <input type="hidden" name="show_ongoing" value="0">
                        <input type="checkbox" id="show_ongoing" name="show_ongoing" value="1" @checked(($settings['show_ongoing'] ?? '1') === '1') class="mt-0.5 rounded text-slate-900 focus:ring-0 cursor-pointer w-4 h-4">
                        <div class="text-xs">
                            <label for="show_ongoing" class="font-bold text-slate-800 block cursor-pointer">
                                Tampilkan daftar "Sedang Kami Kerjakan" di halaman tracking
                            </label>
                            <p class="text-slate-500 text-[11px] mt-1 leading-relaxed">
                                Daftar publik pesanan aktif: nama produk, progress tahap, bulan pesan, dan deadline. <strong class="text-slate-700">Tanpa nomor pesanan, tanggal lengkap, nama customer, maupun nama proyek</strong> untuk menjaga privasi klien.
                            </p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                            Simpan Pengaturan
                        </button>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 6. TAB: SEO & ANALYTICS -->
                <!-- ========================================== -->
                <section data-tab="seo" x-show="tab === 'seo'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                    <div class="pb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Optimasi Mesin Pencari</span>
                        <h3 class="text-base font-bold text-slate-900 mt-0.5">SEO Default & Analytics</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Konfigurasi meta description Google dan tracking pengunjung website.</p>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="seo_title">SEO Title Default</label>
                            <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $settings['seo_title'] ?? '') }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="seo_description">SEO Description Default</label>
                            <textarea id="seo_description" name="seo_description" rows="3" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 leading-relaxed">{{ old('seo_description', $settings['seo_description'] ?? '') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="analytics_id">Google Analytics ID</label>
                                <input type="text" id="analytics_id" name="analytics_id" value="{{ old('analytics_id', $settings['analytics_id'] ?? '') }}" placeholder="G-XXXXXXXXXX" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                            <div>
                                <label class="font-semibold text-slate-700 block mb-1" for="google_site_verification">Google Search Console Verification (Tag HTML)</label>
                                <input type="text" id="google_site_verification" name="google_site_verification" value="{{ old('google_site_verification', $settings['google_site_verification'] ?? '') }}" placeholder="Contoh: google35163a4fcf062a76 atau token verifikasi" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                                <span class="text-[10px] text-slate-400 mt-1 block">Jika menggunakan metode Tag HTML di Google Search Console, masukkan nilai atribut content di sini.</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                            Simpan Pengaturan
                        </button>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 7. TAB: INVOICE -->
                <!-- ========================================== -->
                <section data-tab="invoice" x-show="tab === 'invoice'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                    <div class="pb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Format Penagihan</span>
                        <h3 class="text-base font-bold text-slate-900 mt-0.5">Informasi Invoice & Rekening</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Atur data yang dicetak pada lembar PDF invoice resmi Zada Karya.</p>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="invoice_company_name">Nama Perusahaan di Invoice</label>
                            <input type="text" id="invoice_company_name" name="invoice_company_name" value="{{ old('invoice_company_name', $settings['invoice_company_name'] ?? '') }}" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Emblem di header PDF diambil dari <strong class="text-slate-600">Favicon</strong> (tab Logo & Foto).</span>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="invoice_address">Alamat di Invoice</label>
                            <textarea id="invoice_address" name="invoice_address" rows="2" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">{{ old('invoice_address', $settings['invoice_address'] ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="invoice_bank_info">Informasi Rekening (Tampil di Invoice)</label>
                            <textarea id="invoice_bank_info" name="invoice_bank_info" rows="3.5" placeholder="Bank : BCA&#10;Account Name : MUHAMMAD HILMY RIFAI&#10;Account No. : 4760 214782" class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 leading-relaxed">{{ old('invoice_bank_info', $settings['invoice_bank_info'] ?? '') }}</textarea>
                            <span class="text-[10px] text-slate-400 mt-1 block">Satu baris per keterangan, format <code class="font-mono bg-slate-100 px-1 py-0.5 rounded">Label : Nilai</code> agar rapi di PDF.</span>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="invoice_terms">Catatan / Ketentuan (Bagian Bawah Invoice)</label>
                            <textarea id="invoice_terms" name="invoice_terms" rows="2.5" placeholder="Barang yang sudah dipesan tidak bisa dibatalkan&#10;Pelunasan wajib dilakukan sebelum pengambilan barang" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 leading-relaxed">{{ old('invoice_terms', $settings['invoice_terms'] ?? '') }}</textarea>
                            <span class="text-[10px] text-slate-400 mt-1 block">Satu baris = satu poin. Kosongkan untuk memakai ketentuan bawaan.</span>
                        </div>

                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="invoice_signer">Nama Penandatangan ("Hormat kami")</label>
                            <input type="text" id="invoice_signer" name="invoice_signer" value="{{ old('invoice_signer', $settings['invoice_signer'] ?? '') }}" placeholder="Contoh: Hilmi Rifai — Zada Karya Production" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            <span class="text-[10px] text-slate-400 mt-1 block">Tampil di bawah garis tanda tangan. Kosong = nama perusahaan.</span>
                        </div>

                        <!-- TTD & Stempel 2 Columns -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <!-- Tanda Tangan -->
                            <div class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-2">
                                <span class="font-bold text-slate-800 block">Gambar Tanda Tangan (Opsional)</span>
                                <x-admin.media-picker name="invoice_signature" :current="$settings['invoice_signature'] ?? null">
                                    <span class="inline-flex h-20 items-center rounded-lg bg-white px-4 border border-slate-200">
                                        <img src="{{ asset('storage/'.($settings['invoice_signature'] ?? '')) }}" alt="Tanda tangan" class="max-h-16 w-auto object-contain">
                                    </span>
                                </x-admin.media-picker>
                                <p class="text-[10px] text-slate-400">PNG/WebP latar transparan. Dipasang di kolom "Hormat kami" pada <strong class="text-slate-600">setiap</strong> PDF invoice.</p>
                            </div>

                            <!-- Stempel LUNAS -->
                            <div class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-2">
                                <span class="font-bold text-slate-800 block">Stempel LUNAS (Opsional)</span>
                                <x-admin.media-picker name="invoice_stamp" :current="$settings['invoice_stamp'] ?? null">
                                    <span class="inline-flex h-20 items-center rounded-lg bg-white px-4 border border-slate-200">
                                        <img src="{{ asset('storage/'.($settings['invoice_stamp'] ?? '')) }}" alt="Stempel lunas" class="max-h-16 w-auto object-contain">
                                    </span>
                                </x-admin.media-picker>
                                <p class="text-[10px] text-slate-400">PNG/WebP latar transparan, persegi. Dicetak otomatis <strong class="text-slate-600">hanya saat pesanan berstatus Lunas</strong>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                            Simpan Pengaturan
                        </button>
                    </div>
                </section>

            </form>

            <!-- ========================================== -->
            <!-- 8. TAB: AKUN (GANTI PASSWORD ADMIN) -->
            <!-- ========================================== -->
            <section data-tab="akun" x-show="tab === 'akun'" x-cloak class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-6">
                <div class="pb-4 border-b border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Keamanan Kredensial</span>
                    <h3 class="text-base font-bold text-slate-900 mt-0.5">Akun Admin — Ganti Password</h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Login saat ini sebagai <strong class="text-slate-700 font-mono">{{ auth()->user()->email }}</strong>. Minimal 10 karakter, mengandung huruf dan angka. Sesi di perangkat lain akan diputus setelah diganti.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-4 text-xs max-w-lg" @submit="$el.action = $el.action.split('#')[0] + '#akun'">
                    @csrf @method('PATCH')

                    <div>
                        <label class="font-semibold text-slate-700 block mb-1" for="current_password">Password Saat Ini <span class="text-rose-500">*</span></label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required placeholder="••••••••••••" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="password">Password Baru <span class="text-rose-500">*</span></label>
                            <input type="password" id="password" name="password" autocomplete="new-password" required placeholder="Minimal 10 karakter" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        </div>
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1" for="password_confirmation">Ulangi Password Baru <span class="text-rose-500">*</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required placeholder="Ulangi password" class="w-full bg-slate-50 text-xs rounded-xl px-3.5 py-2.5 text-slate-800 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-colors shadow-xs">
                            Ganti Password
                        </button>
                    </div>
                </form>
            </section>

        </div>

    </div>

</div>
@endsection
