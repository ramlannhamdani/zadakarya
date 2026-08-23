<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageUploader;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public const KEYS = [
        'company_name', 'tagline', 'whatsapp', 'email', 'address', 'city',
        'instagram', 'facebook', 'tiktok', 'google_maps_url', 'footer_text',
        'seo_title', 'seo_description',
        'invoice_company_name', 'invoice_address', 'invoice_bank_info',
        'analytics_id',
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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
