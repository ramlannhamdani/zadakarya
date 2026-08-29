<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public const KEYS = [
        'company_name', 'tagline', 'whatsapp', 'email', 'address', 'city',
        'instagram', 'facebook', 'tiktok', 'google_maps_url', 'footer_text',
        'seo_title', 'seo_description',
        'invoice_company_name', 'invoice_address', 'invoice_bank_info', 'invoice_terms', 'invoice_signer',
        'hero_badge', 'hero_title', 'hero_title_accent', 'hero_text', 'hero_rating_text', 'hero_stats',
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
            'invoice_signature_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'hero_badge' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['nullable', 'string', 'max:200'],
            'hero_title_accent' => ['nullable', 'string', 'max:120'],
            'hero_text' => ['nullable', 'string', 'max:500'],
            'hero_rating_text' => ['nullable', 'string', 'max:100'],
            'hero_stats' => ['nullable', 'string', 'max:1000'],
            'hero_image' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:4096'],
            'hero_image_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
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

        foreach (['logo', 'logo_light', 'invoice_signature'] as $key) {
            if ($request->hasFile($key)) {
                ImageUploader::delete(Setting::get($key));
                [$path] = ImageUploader::store($request->file($key), 'branding', 'public', 600, 200);
                Setting::set($key, $path);
            } elseif ($request->filled($key.'_pick') && ($res = ImageUploader::fromGalleryId($request->input($key.'_pick'), 'branding', 'public', 600, 200))) {
                ImageUploader::delete(Setting::get($key));
                Setting::set($key, $res[0]);
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

        // Foto hero: kalau GD server tidak bisa menulis WebP, ImageUploader akan
        // meng-encode ke JPEG dan transparansi PNG hilang — simpan apa adanya.
        if ($request->hasFile('hero_image')) {
            $file = $request->file('hero_image');
            $keepsAlpha = function_exists('imagewebp')
                || ! in_array(strtolower($file->getClientOriginalExtension()), ['png', 'webp'], true);

            ImageUploader::delete(Setting::get('hero_image'));

            if ($keepsAlpha) {
                [$path] = ImageUploader::store($file, 'hero', 'public', 1400, 480);
            } else {
                $path = $file->storeAs(
                    'hero',
                    now()->format('YmdHis').'-'.Str::random(8).'.'.strtolower($file->getClientOriginalExtension()),
                    'public'
                );
            }

            Setting::set('hero_image', $path);
        } elseif ($request->filled('hero_image_pick') && ($res = ImageUploader::fromGalleryId($request->input('hero_image_pick'), 'hero', 'public', 1400, 480))) {
            ImageUploader::delete(Setting::get('hero_image'));
            Setting::set('hero_image', $res[0]);
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

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
