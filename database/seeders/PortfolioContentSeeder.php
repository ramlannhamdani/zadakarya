<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Konten portfolio awal. Aman dijalankan berulang (firstOrCreate per slug).
 * Jalankan manual: php artisan db:seed --class=PortfolioContentSeeder --force
 */
class PortfolioContentSeeder extends Seeder
{
    /** Gambar yang sudah diupload di server (disk public). */
    private const COVER = 'portfolio/20260824052804-cESbkW6O.webp';

    public function run(): void
    {
        $categories = PortfolioCategory::pluck('id', 'slug');

        $items = [
            [
                'title' => 'Seragam Kerja Lapangan PT Karya Beton',
                'category' => 'seragam',
                'year' => '2026',
                'featured' => true,
                'tags' => ['seragam kerja', 'drill', 'bordir', 'scotchlite'],
                'description' => "Produksi 200 setelan seragam kerja lapangan berbahan American Drill dengan bordir logo perusahaan dan aksen scotchlite untuk keamanan kerja malam hari. Jahitan rantai ganda pada titik beban agar tahan pemakaian harian di lapangan.",
            ],
            [
                'title' => 'Kemeja Seragam Kantor Distributor Elektronik',
                'category' => 'kantor',
                'year' => '2026',
                'featured' => true,
                'tags' => ['kemeja kantor', 'tropical', 'bordir logo'],
                'description' => "Kemeja seragam kantor bahan tropical premium untuk 85 karyawan, dengan bordir logo di dada dan varian lengan panjang untuk staf manajemen. Warna disesuaikan dengan identitas brand perusahaan.",
            ],
            [
                'title' => 'Seragam Batik Sekolah SMK Bina Nusantara',
                'category' => 'sekolah',
                'year' => '2025',
                'featured' => false,
                'tags' => ['seragam sekolah', 'batik', 'custom motif'],
                'description' => "Pengerjaan 350 seragam batik dengan motif khusus yang didesain bersama pihak sekolah. Bahan katun halus yang adem dipakai seharian, ukuran lengkap dari S hingga XXL.",
            ],
            [
                'title' => 'Jersey Futsal Turnamen Antar RT Se-Kecamatan',
                'category' => 'olahraga',
                'year' => '2026',
                'featured' => true,
                'tags' => ['jersey', 'futsal', 'printing sublim', 'dryfit'],
                'description' => "Jersey futsal full printing sublimasi untuk 12 tim peserta turnamen, masing-masing dengan desain, nama punggung, dan nomor berbeda. Bahan dryfit ringan dan cepat kering.",
            ],
            [
                'title' => 'Polo Shirt Komunitas Pecinta Alam',
                'category' => 'polo',
                'year' => '2025',
                'featured' => false,
                'tags' => ['polo shirt', 'lacoste cvc', 'bordir'],
                'description' => "Polo shirt lacoste CVC 120 pcs untuk komunitas pecinta alam dengan bordir logo komunitas di dada dan lengan. Kerah dan manset rib yang tidak mudah melar.",
            ],
            [
                'title' => 'Kaos Sablon Event Gathering Perusahaan',
                'category' => 'kaos',
                'year' => '2026',
                'featured' => true,
                'tags' => ['kaos sablon', 'combed 30s', 'plastisol', 'event'],
                'description' => "Kaos gathering tahunan 300 pcs berbahan cotton combed 30s dengan sablon plastisol 4 warna. Desain custom dari tim internal perusahaan kami sempurnakan agar hasil sablon tajam dan awet.",
            ],
            [
                'title' => 'Celana Training Ekstrakurikuler Sekolah',
                'category' => 'celana',
                'year' => '2025',
                'featured' => false,
                'tags' => ['celana training', 'lotto', 'sekolah'],
                'description' => "Celana training bahan lotto untuk kegiatan ekstrakurikuler, 250 pcs dengan list warna khas sekolah dan saku ritsleting. Karet pinggang premium yang nyaman untuk aktivitas olahraga.",
            ],
            [
                'title' => 'Kaos Custom Merchandise Coffee Shop',
                'category' => 'custom',
                'year' => '2026',
                'featured' => false,
                'tags' => ['merchandise', 'kaos custom', 'dtf'],
                'description' => "Merchandise kaos untuk coffee shop lokal dengan artwork ilustrasi full color menggunakan teknik DTF. Dikerjakan bertahap mengikuti stok penjualan, mulai dari 24 pcs per batch.",
            ],
            [
                'title' => 'Seragam Olahraga Guru dan Staff Yayasan',
                'category' => 'olahraga',
                'year' => '2024',
                'featured' => false,
                'tags' => ['seragam olahraga', 'dryfit', 'setelan'],
                'description' => "Setelan seragam olahraga (atasan dan celana) untuk 90 guru dan staff yayasan pendidikan. Bahan dryfit misty dengan kombinasi warna dan bordir logo yayasan.",
            ],
            [
                'title' => 'Polo Shirt Panitia Wisuda Universitas',
                'category' => 'polo',
                'year' => '2024',
                'featured' => false,
                'tags' => ['polo shirt', 'panitia', 'event kampus'],
                'description' => "Polo shirt identitas panitia acara wisuda, 150 pcs dengan bordir logo universitas dan tulisan divisi di lengan. Pengerjaan selesai 10 hari sebelum hari acara.",
            ],
        ];

        foreach ($items as $item) {
            Portfolio::firstOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'title' => $item['title'],
                    'portfolio_category_id' => $categories[$item['category']] ?? null,
                    'description' => $item['description'],
                    'cover_image' => self::COVER,
                    'tags' => $item['tags'],
                    'production_year' => $item['year'],
                    'is_featured' => $item['featured'],
                    'is_published' => true,
                ]
            );
        }
    }
}
