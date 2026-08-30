<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public const KEYS = [
        'company_name', 'tagline', 'whatsapp', 'email', 'address', 'city',
        'instagram', 'facebook', 'tiktok', 'google_maps_url', 'footer_text',
        'seo_title', 'seo_description',
        'invoice_company_name', 'invoice_address', 'invoice_bank_info', 'invoice_terms', 'invoice_signer',
        'hero_badge', 'hero_title', 'hero_title_accent', 'hero_text', 'hero_rating_text', 'hero_stats', 'hero_image_style',
        'analytics_id', 'show_ongoing',
    ];

    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => Setting::all_cached(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:300'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'instagram' => ['nullable', 'string', 'max:200'],
            'facebook' => ['nullable', 'string', 'max:200'],
            'tiktok' => ['nullable', 'string', 'max:200'],
            'google_maps_url' => ['nullable', 'url', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'invoice_company_name' => ['nullable', 'string', 'max:150'],
            'invoice_address' => ['nullable', 'string', 'max:500'],
            'invoice_bank_info' => ['nullable', 'string', 'max:1000'],
            'invoice_terms' => ['nullable', 'string', 'max:2000'],
            'invoice_signer' => ['nullable', 'string', 'max:150'],
            'invoice_signature' => ['nullable', 'image', 'mimes:png,webp', 'max:1024'],
            'invoice_stamp' => ['nullable', 'image', 'mimes:png,webp', 'max:1024'],
            'invoice_stamp_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'invoice_signature_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'hero_badge' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['nullable', 'string', 'max:200'],
            'hero_title_accent' => ['nullable', 'string', 'max:120'],
            'hero_text' => ['nullable', 'string', 'max:500'],
            'hero_rating_text' => ['nullable', 'string', 'max:100'],
            'hero_stats' => ['nullable', 'string', 'max:1000'],
            'hero_image' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:4096'],
            'hero_image_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'hero_image_style' => ['nullable', 'in:cutout,framed'],
            'analytics_id' => ['nullable', 'string', 'max:50'],
            'show_ongoing' => ['nullable', 'in:0,1'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logo_light' => ['nullable', 'image', 'mimes:png,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png', 'max:512'],
            'workshop_photo_1' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'workshop_photo_2' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'workshop_photo_3' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'logo_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'logo_light_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'workshop_photo_1_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'workshop_photo_2_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'workshop_photo_3_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
        ]);

        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $data)) {
                Setting::set($key, $data[$key]);
            }
        }

        foreach (['logo', 'logo_light', 'invoice_signature', 'invoice_stamp'] as $key) {
            $stored = null;

            if ($request->hasFile($key)) {
                ImageUploader::delete(Setting::get($key));
                $stored = $this->storeKeepingAlpha($request->file($key), 'branding', 600, 200);
            } elseif ($request->filled($key.'_pick') && ($res = ImageUploader::fromGalleryId($request->input($key.'_pick'), 'branding', 'public', 600, 200))) {
                ImageUploader::delete(Setting::get($key));
                $stored = $res[0];
            }

            if ($stored === null) {
                continue;
            }

            Setting::set($key, $stored);

            // Tanda tangan & stempel biasanya digambar di kanvas besar dengan
            // banyak ruang kosong; dipotong agar tampil proporsional di PDF.
            if (in_array($key, ['invoice_signature', 'invoice_stamp'], true)) {
                ImageUploader::trimTransparent(Storage::disk('public')->path($stored));
            }
        }

        foreach (['workshop_photo_1', 'workshop_photo_2', 'workshop_photo_3'] as $key) {
            if ($request->hasFile($key)) {
                ImageUploader::delete(Setting::get($key));
                [$path] = ImageUploader::store($request->file($key), 'workshop', 'public', 1000);
                Setting::set($key, $path);
            } elseif ($request->filled($key.'_pick') && ($res = ImageUploader::fromGalleryId($request->input($key.'_pick'), 'workshop', 'public', 1000))) {
                ImageUploader::delete(Setting::get($key));
                Setting::set($key, $res[0]);
            }
        }

        if ($request->hasFile('hero_image')) {
            ImageUploader::delete(Setting::get('hero_image'));
            $path = $this->storeKeepingAlpha($request->file('hero_image'), 'hero', 1400, 480);
            Setting::set('hero_image', $path);
            $this->detectHeroStyle($path);
        } elseif ($request->filled('hero_image_pick') && ($res = ImageUploader::fromGalleryId($request->input('hero_image_pick'), 'hero', 'public', 1400, 480))) {
            ImageUploader::delete(Setting::get('hero_image'));
            Setting::set('hero_image', $res[0]);
            $this->detectHeroStyle($res[0]);
        }

        if ($request->hasFile('favicon')) {
            // Disimpan apa adanya (GD tidak bisa memproses .ico); nama acak agar cache browser terganti.
            ImageUploader::delete(Setting::get('favicon'));
            $file = $request->file('favicon');
            $path = $file->storeAs(
                'branding',
                'favicon-'.Str::random(6).'.'.strtolower($file->getClientOriginalExtension()),
                'public'
            );
            Setting::set('favicon', $path);
        }

        $this->removeMarkedImages($request);

        return back()->with('success', 'Pengaturan disimpan.');
    }

    /**
     * Kosongkan gambar yang ditandai hapus lewat tombol X di form.
     * Upload/pilihan baru selalu menang, jadi tanda hapus diabaikan
     * bila pada field yang sama juga dikirim gambar pengganti.
     */
    private function removeMarkedImages(Request $request): void
    {
        $keys = [
            'logo', 'logo_light', 'favicon', 'hero_image',
            'invoice_signature', 'invoice_stamp',
            'workshop_photo_1', 'workshop_photo_2', 'workshop_photo_3',
        ];

        foreach ($keys as $key) {
            if (! $request->boolean('remove_'.$key)) {
                continue;
            }

            if ($request->hasFile($key) || $request->filled($key.'_pick')) {
                continue;
            }

            ImageUploader::delete(Setting::get($key));
            Setting::set($key, null);

            if ($key === 'hero_image') {
                Setting::set('hero_image_style', 'framed');
            }
        }
    }

    /**
     * Simpan gambar yang transparansinya penting (logo putih, tanda tangan,
     * stempel, foto hero potongan). ImageUploader meng-encode ke WebP bila GD
     * server mendukungnya — alpha aman; kalau tidak, hasilnya JPEG dan latar
     * transparan berubah jadi blok putih, jadi file PNG/WebP disimpan apa adanya.
     */
    private function storeKeepingAlpha(\Illuminate\Http\UploadedFile $file, string $dir, int $maxWidth, int $thumbWidth): string
    {
        $isAlphaFormat = in_array(strtolower($file->getClientOriginalExtension()), ['png', 'webp'], true);

        if (function_exists('imagewebp') || ! $isAlphaFormat) {
            [$path] = ImageUploader::store($file, $dir, 'public', $maxWidth, $thumbWidth);

            return $path;
        }

        return $file->storeAs(
            $dir,
            now()->format('YmdHis').'-'.Str::random(8).'.'.strtolower($file->getClientOriginalExtension()),
            'public'
        );
    }

    /**
     * Gambar baru menentukan gaya tampilnya: latar transparan = potongan model
     * yang berdiri di atas bidang maroon, selain itu ditampilkan sebagai foto
     * berbingkai. Admin tetap bisa mengubahnya lewat pilihan di form.
     */
    private function detectHeroStyle(string $path): void
    {
        Setting::set(
            'hero_image_style',
            ImageUploader::hasTransparentEdges(Storage::disk('public')->path($path)) ? 'cutout' : 'framed'
        );
    }
}
