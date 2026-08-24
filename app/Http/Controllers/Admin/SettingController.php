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
        'invoice_company_name', 'invoice_address', 'invoice_bank_info',
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
            'analytics_id' => ['nullable', 'string', 'max:50'],
            'show_ongoing' => ['nullable', 'in:0,1'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logo_light' => ['nullable', 'image', 'mimes:png,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png', 'max:512'],
            'workshop_photo_1' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'workshop_photo_2' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'workshop_photo_3' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $data)) {
                Setting::set($key, $data[$key]);
            }
        }

        if ($request->hasFile('logo')) {
            ImageUploader::delete(Setting::get('logo'));
            [$path] = ImageUploader::store($request->file('logo'), 'branding', 'public', 600, 200);
            Setting::set('logo', $path);
        }

        if ($request->hasFile('logo_light')) {
            ImageUploader::delete(Setting::get('logo_light'));
            [$path] = ImageUploader::store($request->file('logo_light'), 'branding', 'public', 600, 200);
            Setting::set('logo_light', $path);
        }

        foreach (['workshop_photo_1', 'workshop_photo_2', 'workshop_photo_3'] as $key) {
            if ($request->hasFile($key)) {
                ImageUploader::delete(Setting::get($key));
                [$path] = ImageUploader::store($request->file($key), 'workshop', 'public', 1000);
                Setting::set($key, $path);
            }
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
