<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

class BiolinkController extends Controller
{
    public function index()
    {
        $siteName = setting('company_name', 'Zada Karya Production');
        $tagline = setting('tagline', 'Clothing & Custom Apparel');

        $admin1Phone = setting('whatsapp', '+62 812-9100-2362');
        $admin2Phone = setting('whatsapp_2') ?: $admin1Phone;

        $admin1Link = wa_send_link($admin1Phone, 'Halo Admin 1 Zada Karya, saya ingin konsultasi dan pemesanan konveksi.');
        $admin2Link = wa_send_link($admin2Phone, 'Halo Admin 2 Zada Karya, saya ingin konsultasi dan pemesanan konveksi.');

        $tiktokUrl = setting('tiktok', 'https://www.tiktok.com/@zadakarya');
        $youtubeUrl = setting('youtube', 'https://www.youtube.com/@zadakarya');
        $instagramUrl = setting('instagram', 'https://www.instagram.com/zadakarya.id');

        $mapsUrl = setting('google_maps_url')
            ?: 'https://maps.google.com/?q=' . urlencode(setting('company_name', 'Zada Karya Production') . ' ' . setting('address', 'Sudimara Tangerang'));

        $links = [
            [
                'id' => 'link-website',
                'title' => 'Website',
                'subtitle' => 'Lihat produk & layanan kami',
                'url' => route('home'),
                'is_external' => false,
                'theme' => 'maroon',
                'icon' => 'globe',
            ],
            [
                'id' => 'link-tiktok',
                'title' => 'TikTok',
                'subtitle' => 'Konten proses, hasil & inspirasi',
                'url' => $tiktokUrl ?: 'https://www.tiktok.com',
                'is_external' => true,
                'theme' => 'light',
                'icon' => 'tiktok',
            ],
            [
                'id' => 'link-youtube',
                'title' => 'YouTube',
                'subtitle' => 'Video proses & cerita di balik karya',
                'url' => $youtubeUrl ?: 'https://www.youtube.com',
                'is_external' => true,
                'theme' => 'light',
                'icon' => 'youtube',
            ],
            [
                'id' => 'link-admin-1',
                'title' => 'Chat Admin 1',
                'subtitle' => 'Konsultasi & pemesanan',
                'url' => $admin1Link,
                'is_external' => true,
                'theme' => 'maroon',
                'icon' => 'whatsapp',
            ],
            [
                'id' => 'link-admin-2',
                'title' => 'Chat Admin 2',
                'subtitle' => 'Konsultasi & pemesanan',
                'url' => $admin2Link,
                'is_external' => true,
                'theme' => 'light',
                'icon' => 'whatsapp',
            ],
            [
                'id' => 'link-maps',
                'title' => 'Lokasi Workshop',
                'subtitle' => 'Lihat kami di Google Maps',
                'url' => $mapsUrl,
                'is_external' => true,
                'theme' => 'maroon',
                'icon' => 'maps',
            ],
        ];

        return view('site.biolink', compact(
            'siteName',
            'tagline',
            'links',
            'instagramUrl',
            'tiktokUrl',
            'youtubeUrl',
            'admin1Link',
            'admin1Phone',
            'admin2Phone'
        ));
    }
}
