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
            [
                'name' => 'Hoodie',
                'slug' => 'hoodie',
                'sort_order' => 8,
                'short_description' => 'Produksi hoodie custom bahan fleece dengan jahitan rapi — cocok untuk komunitas, sekolah, brand, dan merchandise.',
                'description' => '<p>Kami memproduksi <strong>hoodie custom</strong> untuk komunitas, sekolah, kampus, perusahaan, hingga brand clothing. Model pullover maupun zipper dapat dibuat sesuai desain Anda, lengkap dengan pilihan warna, ketebalan bahan, dan teknik dekorasi.</p>'
                    .'<h2>Yang Bisa Dikustom</h2>'
                    .'<ul><li>Model pullover (tanpa resleting) atau zipper depan</li><li>Warna badan, hoodie, tali, dan rib</li><li>Sablon dada, punggung, lengan, atau bordir logo</li><li>Kantong kanguru, saku samping, atau tanpa kantong</li><li>Label ukuran, hangtag, dan kemasan satuan</li></ul>'
                    .'<p>Kami sarankan membuat <strong>sample</strong> terlebih dulu untuk pesanan dalam jumlah besar, agar ukuran, warna, dan hasil sablon dapat dipastikan sebelum produksi massal berjalan.</p>',
                'features' => [
                    'Bahan fleece cotton, CVC fleece, atau babyterry',
                    'Model pullover dan zipper (resleting)',
                    'Sablon plastisol, DTF, atau bordir logo',
                    'Kantong kanguru, tali hoodie, dan rib pergelangan',
                    'Ukuran S–XXXL, tersedia ukuran custom',
                ],
                'material_info' => 'Fleece cotton (CTC) 280–320 gsm untuk hoodie tebal dan hangat. CVC fleece lebih adem, tidak mudah melar, dan warnanya awet. Babyterry ringan dan tipis, cocok untuk daerah bersuhu panas atau pemakaian harian. Bagian rib pergelangan dan bawah memakai bahan rib agar bentuk tetap terjaga setelah dicuci berulang.',
                'production_info' => 'Estimasi pengerjaan 10–21 hari kerja tergantung jumlah, jenis bahan, dan teknik dekorasi. Sample dapat dibuat lebih dulu untuk memastikan ukuran dan warna. Tersedia opsi pengerjaan express untuk kebutuhan mendesak — silakan konfirmasi saat konsultasi.',
                'min_order' => '12 pcs',
                'seo_title' => 'Konveksi Hoodie Custom — Bahan Fleece, Sablon & Bordir',
                'seo_description' => 'Jasa pembuatan hoodie custom untuk komunitas, sekolah, dan brand. Bahan fleece cotton, CVC, dan babyterry dengan sablon atau bordir. Minimum order 12 pcs.',
            ],
            [
                'name' => 'Rompi',
                'slug' => 'rompi',
                'sort_order' => 9,
                'short_description' => 'Produksi rompi kerja, rompi safety, dan rompi komunitas dengan bahan kuat serta aksen scotchlite sesuai kebutuhan lapangan.',
                'description' => '<p>Kami memproduksi <strong>rompi custom</strong> untuk kebutuhan kerja lapangan, proyek, instansi, relawan, hingga komunitas. Jumlah dan posisi kantong, jenis bahan, serta aksen reflektif dapat disesuaikan dengan kebutuhan pemakaian di lapangan.</p>'
                    .'<h2>Jenis Rompi yang Kami Kerjakan</h2>'
                    .'<ul><li>Rompi kerja dan proyek dengan banyak kantong</li><li>Rompi safety dengan scotchlite reflektif</li><li>Rompi instansi, relawan, dan panitia acara</li><li>Rompi outdoor berbahan taslan atau mesh</li></ul>'
                    .'<p>Logo instansi dapat dipasang dengan <strong>bordir</strong> untuk hasil yang lebih rapi dan tahan lama, atau <strong>sablon</strong> bila desainnya berwarna banyak.</p>',
                'features' => [
                    'Rompi kerja, safety, proyek, dan komunitas',
                    'Bahan drill, kanvas, taslan, atau mesh',
                    'Aksen scotchlite (reflektor) untuk keselamatan kerja',
                    'Jumlah dan model kantong sesuai kebutuhan',
                    'Bordir atau sablon logo instansi',
                ],
                'material_info' => 'American drill dan Japan drill untuk rompi kerja yang kuat dan tahan pemakaian harian. Kanvas untuk tampilan lebih tebal dan kokoh. Taslan tahan angin serta gerimis ringan, cocok untuk aktivitas luar ruangan. Mesh atau jaring untuk sirkulasi udara pada pemakaian di area panas. Scotchlite reflektif tersedia untuk kebutuhan keselamatan kerja.',
                'production_info' => 'Estimasi pengerjaan 7–18 hari kerja tergantung jumlah, jenis bahan, dan detail kantong. Ukuran dapat disesuaikan per orang untuk kebutuhan instansi. Pengukuran massal dapat dibantu dengan size chart atau sample ukuran.',
                'min_order' => '24 pcs',
                'seo_title' => 'Konveksi Rompi Custom — Rompi Kerja, Safety & Komunitas',
                'seo_description' => 'Jasa pembuatan rompi kerja, rompi safety scotchlite, dan rompi komunitas. Bahan drill, kanvas, taslan, dan mesh. Minimum order 24 pcs.',
            ],
        ];

        foreach ($services as $i => $service) {
            $default = "<p>{$service['short_description']}</p><p>Zada Karya Production mengerjakan setiap pesanan melalui proses yang terukur: konsultasi kebutuhan, penawaran, persetujuan desain, produksi, quality check, hingga pengiriman. Hubungi kami melalui WhatsApp untuk konsultasi kebutuhan dan estimasi harga.</p>";

            Service::firstOrCreate(
                ['slug' => $service['slug'] ?? Str::slug($service['name'])],
                [
                    'name' => $service['name'],
                    'short_description' => $service['short_description'],
                    'description' => $service['description'] ?? $default,
                    'features' => $service['features'],
                    'min_order' => $service['min_order'],
                    'production_info' => $service['production_info'] ?? 'Estimasi pengerjaan menyesuaikan jumlah dan tingkat kerumitan pesanan. Timeline akan dikonfirmasi saat konsultasi.',
                    'material_info' => $service['material_info'] ?? 'Pilihan bahan akan direkomendasikan sesuai kebutuhan pemakaian dan budget.',
                    'seo_title' => $service['seo_title'] ?? null,
                    'seo_description' => $service['seo_description'] ?? null,
                    'is_published' => true,
                    'sort_order' => $service['sort_order'] ?? $i,
                ]
            );
        }
    }
}
