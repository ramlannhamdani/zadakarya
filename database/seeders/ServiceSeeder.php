<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Seragam Kerja',
                'short_description' => 'Produksi seragam kerja lapangan dan operasional dengan bahan kuat dan nyaman untuk pemakaian harian.',
                'features' => ['Bahan drill, tropical, atau sesuai kebutuhan', 'Bordir logo perusahaan', 'Ukuran S–XXXL dan custom', 'Konsultasi model dan bahan'],
                'min_order' => '24 pcs',
            ],
            [
                'name' => 'Seragam Sekolah',
                'short_description' => 'Seragam sekolah dengan jahitan rapi dan bahan yang nyaman untuk aktivitas siswa sehari-hari.',
                'features' => ['Bahan adem dan mudah dirawat', 'Jahitan kuat untuk pemakaian harian', 'Berbagai ukuran anak hingga dewasa'],
                'min_order' => '24 pcs',
            ],
            [
                'name' => 'Seragam Kantor',
                'short_description' => 'Kemeja dan seragam kantor dengan tampilan profesional yang merepresentasikan identitas perusahaan.',
                'features' => ['Model formal dan semi-formal', 'Bordir atau sablon logo', 'Pilihan bahan premium'],
                'min_order' => '24 pcs',
            ],
            [
                'name' => 'Seragam Olahraga',
                'short_description' => 'Jersey dan seragam olahraga dengan bahan ringan, menyerap keringat, dan nyaman dipakai bergerak.',
                'features' => ['Bahan dryfit / jersey', 'Printing sublim full color', 'Custom desain tim'],
                'min_order' => '12 pcs',
            ],
            [
                'name' => 'Polo Shirt',
                'short_description' => 'Polo shirt untuk seragam komunitas, kantor, dan merchandise dengan bahan lacoste pilihan.',
                'features' => ['Bahan lacoste cotton / CVC / PE', 'Bordir logo tajam dan rapi', 'Pilihan warna lengkap'],
                'min_order' => '24 pcs',
            ],
            [
                'name' => 'Kaos Sablon',
                'short_description' => 'Kaos custom dengan sablon berkualitas untuk komunitas, event, promosi, dan merchandise.',
                'features' => ['Cotton combed 24s / 30s', 'Sablon plastisol, rubber, DTF', 'Desain custom sesuai kebutuhan'],
                'min_order' => '24 pcs',
            ],
            [
                'name' => 'Pembuatan Celana',
                'short_description' => 'Produksi celana kerja, celana seragam, training, dan celana custom lainnya.',
                'features' => ['Celana kerja dan lapangan', 'Celana training olahraga', 'Ukuran dan model custom'],
                'min_order' => '24 pcs',
            ],
            [
                'name' => 'Jahit Custom',
                'short_description' => 'Layanan jahit dan produksi apparel custom sesuai desain dan kebutuhan spesifik Anda.',
                'features' => ['Konsultasi desain dan bahan', 'Sample sebelum produksi massal', 'Quality check setiap tahap'],
                'min_order' => 'Sesuai kebutuhan',
            ],
        ];

        foreach ($services as $i => $service) {
            Service::firstOrCreate(
                ['slug' => Str::slug($service['name'])],
                [
                    'name' => $service['name'],
                    'short_description' => $service['short_description'],
                    'description' => "<p>{$service['short_description']}</p><p>Zada Karya Production mengerjakan setiap pesanan melalui proses yang terukur: konsultasi kebutuhan, penawaran, persetujuan desain, produksi, quality check, hingga pengiriman. Hubungi kami melalui WhatsApp untuk konsultasi kebutuhan dan estimasi harga.</p>",
                    'features' => $service['features'],
                    'min_order' => $service['min_order'],
                    'production_info' => 'Estimasi pengerjaan menyesuaikan jumlah dan tingkat kerumitan pesanan. Timeline akan dikonfirmasi saat konsultasi.',
                    'material_info' => 'Pilihan bahan akan direkomendasikan sesuai kebutuhan pemakaian dan budget.',
                    'is_published' => true,
                    'sort_order' => $i,
                ]
            );
        }
    }
}
