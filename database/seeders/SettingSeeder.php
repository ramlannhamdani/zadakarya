<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\HeroDefaults;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'company_name' => 'Zada Karya Production',
            'tagline' => 'Solusi Produksi Konveksi untuk Kebutuhan Anda',
            'whatsapp' => '+62 812-9100-2362',
            'email' => 'zadakarya.id@gmail.com',
            'address' => 'Jl. Masjid 1 No.17, RT.2/RW.10, Sudimara',
            'city' => 'Tangerang',
            'footer_text' => 'Jasa konveksi dan produksi garment custom: seragam, polo shirt, kaos sablon, celana, dan apparel lainnya.',
            'seo_title' => 'Zada Karya Production — Jasa Konveksi & Garment Custom',
            'seo_description' => 'Zada Karya Production adalah jasa konveksi untuk seragam kerja, seragam sekolah, polo shirt, kaos sablon, celana, dan kebutuhan garment custom dengan proses produksi terukur.',
            'invoice_company_name' => 'Zada Karya Production',
            'invoice_address' => "Jl. Masjid 1 No.17, RT.2/RW.10, Sudimara",
            'invoice_bank_info' => "Bank : BCA
Account Name : Zada Karya Production
Account No. : -",
            'invoice_terms' => "Barang yang sudah dipesan tidak bisa dibatalkan
Pelunasan wajib dilakukan sebelum pengambilan barang
Terima kasih telah mempercayakan konveksi kepada kami",
        ] + HeroDefaults::all();

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
