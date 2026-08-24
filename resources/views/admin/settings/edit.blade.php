@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl">
    @csrf @method('PATCH')

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
            <div>
                <label class="form-label" for="logo">Logo (latar terang)</label>
                @if(!empty($settings['logo']))
                    <img src="{{ asset('storage/'.$settings['logo']) }}" alt="Logo" class="mb-2 h-12 object-contain">
                @endif
                <input class="form-input !py-2" type="file" id="logo" name="logo" accept="image/*">
                <p class="mt-1 text-xs text-neutral-500">Dipakai di navbar. Jika ada, teks nama brand disembunyikan (tetap terbaca SEO).</p>
            </div>
            <div>
                <label class="form-label" for="logo_light">Logo Putih (latar gelap)</label>
                @if(!empty($settings['logo_light']))
                    <span class="mb-2 inline-block rounded-lg bg-brand-800 p-2">
                        <img src="{{ asset('storage/'.$settings['logo_light']) }}" alt="Logo putih" class="h-10 object-contain">
                    </span>
                @endif
                <input class="form-input !py-2" type="file" id="logo_light" name="logo_light" accept=".png,.webp">
                <p class="mt-1 text-xs text-neutral-500">Dipakai di footer. Wajib PNG/WebP dengan latar transparan.</p>
            </div>
            <div>
                <label class="form-label" for="favicon">Favicon</label>
                @if(!empty($settings['favicon']))
                    <img src="{{ asset('storage/'.$settings['favicon']) }}" alt="Favicon" class="mb-2 h-8 w-8 object-contain">
                @endif
                <input class="form-input !py-2" type="file" id="favicon" name="favicon" accept=".ico,.png">
                <p class="mt-1 text-xs text-neutral-500">Ikon tab browser. PNG atau ICO persegi, disarankan minimal 48&times;48 px, maks 512 KB.</p>
            </div>
        </div>
    </div>

    <div class="admin-card mt-5">
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

    <div class="admin-card mt-5">
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

    <div class="admin-card mt-5">
        <h2 class="font-extrabold text-ink">Informasi Invoice</h2>
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
                <textarea class="form-input" id="invoice_bank_info" name="invoice_bank_info" rows="3" placeholder="Bank BCA&#10;No. Rek: xxxxxxxx&#10;a.n. Zada Karya Production">{{ old('invoice_bank_info', $settings['invoice_bank_info'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-primary mt-6">Simpan Pengaturan</button>
</form>
@endsection
