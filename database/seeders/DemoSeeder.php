<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Review;
use App\Models\User;
use App\Support\Sequence;
use App\Support\Stages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Local-only demo data so every feature can be tested immediately.
 * Never runs in production (see DatabaseSeeder).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedReviews();

        if (Order::exists()) {
            return;
        }

        $admin = User::first();

        // Demo customer + order at stage 4 (Proses Produksi), DP paid.
        $customer = Customer::create([
            'name' => 'Budi Santoso',
            'company' => 'PT Contoh Abadi',
            'whatsapp' => '+62 812-0000-1111',
            'email' => 'budi@contoh-abadi.co.id',
            'address' => 'Jl. Contoh Raya No. 10',
            'city' => 'Jakarta',
            'notes' => 'Customer demo untuk pengujian sistem.',
        ]);

        $order = DB::transaction(function () use ($customer) {
            $order = Order::create([
                'order_number' => Sequence::orderNumber(),
                'customer_id' => $customer->id,
                'name' => 'Seragam Polo PT Contoh Abadi 2026',
                'dp_amount' => 5000000,
                'deadline' => now()->addDays(21)->toDateString(),
                'estimated_completion' => now()->addDays(18)->toDateString(),
                'notes' => 'Catatan internal: prioritas sedang.',
            ]);

            $order->items()->createMany([
                ['product_name' => 'Polo Shirt Lacoste CVC', 'description' => 'Bordir logo dada kiri', 'quantity' => 100, 'unit' => 'pcs', 'unit_price' => 85000, 'total' => 8500000, 'sort_order' => 0],
                ['product_name' => 'Celana Chino', 'description' => 'Warna khaki', 'quantity' => 20, 'unit' => 'pcs', 'unit_price' => 75000, 'total' => 1500000, 'sort_order' => 1],
            ]);

            $order->createInitialStages();
            $order->refreshTotals();

            return $order;
        });

        // Advance to stage 4: stages 1-3 completed, 4 in progress.
        foreach ([1, 2, 3] as $n) {
            $order->stages()->where('stage_number', $n)->update([
                'status' => Stages::STATUS_COMPLETED,
                'started_at' => now()->subDays(8 - $n),
                'completed_at' => now()->subDays(7 - $n),
                'updated_by' => $admin?->id,
            ]);
        }
        $order->stages()->where('stage_number', 4)->update([
            'status' => Stages::STATUS_IN_PROGRESS,
            'started_at' => now()->subDay(),
            'note' => 'Proses jahit berjalan, estimasi 60% selesai.',
            'updated_by' => $admin?->id,
        ]);
        $order->update(['current_stage' => 4]);
        $order->logActivity('Pesanan demo dibuat (seeder)', $admin?->id);

        // DP payment.
        $order->payments()->create([
            'amount' => 5000000,
            'payment_date' => now()->subDays(6)->toDateString(),
            'method' => 'transfer',
            'reference' => 'TRF-DEMO-001',
            'note' => 'DP 50%',
            'recorded_by' => $admin?->id,
        ]);
        $order->refreshPaymentStatus();

        // Demo inquiry.
        Inquiry::create([
            'name' => 'Siti Rahma',
            'company' => 'SMK Harapan Bangsa',
            'whatsapp' => '+62 813-2222-3333',
            'email' => 'siti@harapanbangsa.sch.id',
            'service_name' => 'Seragam Sekolah',
            'estimated_quantity' => '300 pcs',
            'target_date' => now()->addMonths(2)->toDateString(),
            'description' => 'Butuh seragam batik dan olahraga untuk tahun ajaran baru.',
        ]);

        // Demo portfolio items.
        $categories = PortfolioCategory::pluck('id', 'slug');
        $portfolios = [
            ['title' => 'Seragam Kerja Kontraktor 2026', 'category' => 'seragam', 'year' => '2026', 'description' => 'Produksi 250 setelan seragam kerja lapangan bahan drill dengan bordir logo dan scotchlite.'],
            ['title' => 'Polo Shirt Komunitas Otomotif', 'category' => 'polo', 'year' => '2026', 'description' => 'Polo shirt lacoste CVC dengan bordir logo komunitas, produksi 120 pcs.'],
            ['title' => 'Jersey Futsal Antar Divisi', 'category' => 'olahraga', 'year' => '2025', 'description' => 'Jersey printing sublim full color untuk turnamen internal perusahaan, 8 tim.'],
        ];
        foreach ($portfolios as $i => $p) {
            Portfolio::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($p['title'])],
                [
                    'title' => $p['title'],
                    'portfolio_category_id' => $categories[$p['category']] ?? null,
                    'description' => $p['description'],
                    'production_year' => $p['year'],
                    'is_featured' => $i === 0,
                    'is_published' => true,
                ]
            );
        }

        // Demo articles.
        $catTips = ArticleCategory::where('slug', 'tips-panduan')->first();
        $articles = [
            [
                'title' => 'Cara Memilih Bahan Seragam yang Tepat',
                'excerpt' => 'Panduan singkat memilih bahan seragam sesuai kebutuhan pemakaian: drill, tropical, hingga oxford.',
                'content' => '<p>Memilih bahan seragam yang tepat menentukan kenyamanan dan keawetan seragam. Berikut beberapa bahan yang paling sering digunakan.</p><h2>Drill</h2><p>Bahan drill kuat dan tebal, cocok untuk seragam kerja lapangan. Tersedia dalam beberapa gramasi seperti American Drill dan Japan Drill.</p><h2>Tropical</h2><p>Lebih ringan dari drill, adem, dan cocok untuk seragam kantor atau kegiatan indoor.</p><h2>Oxford</h2><p>Sering digunakan untuk kemeja seragam dengan tampilan halus dan profesional.</p><p>Masih bingung memilih bahan? Konsultasikan kebutuhan Anda dengan tim Zada Karya Production melalui WhatsApp.</p>',
            ],
            [
                'title' => 'Perbedaan Sablon Plastisol, Rubber, dan DTF',
                'excerpt' => 'Kenali kelebihan masing-masing teknik sablon sebelum memproduksi kaos custom Anda.',
                'content' => '<p>Teknik sablon memengaruhi hasil akhir, ketahanan, dan biaya produksi kaos. Tiga teknik yang paling umum adalah plastisol, rubber, dan DTF.</p><h2>Plastisol</h2><p>Tinta berbasis minyak dengan hasil tajam dan awet, cocok untuk desain detail.</p><h2>Rubber</h2><p>Tinta karet yang elastis dan ekonomis, pilihan populer untuk produksi massal.</p><h2>DTF (Direct to Film)</h2><p>Cocok untuk desain full color dengan jumlah produksi kecil hingga menengah.</p>',
            ],
            [
                'title' => 'Alur Pemesanan Konveksi di Zada Karya Production',
                'excerpt' => 'Dari konsultasi hingga pengiriman — begini alur produksi pesanan Anda di Zada Karya Production.',
                'content' => '<p>Setiap pesanan di Zada Karya Production melewati proses yang terukur agar hasil sesuai harapan.</p><ol><li><strong>Konsultasi</strong> — sampaikan kebutuhan Anda melalui WhatsApp atau form konsultasi.</li><li><strong>Penawaran</strong> — kami kirimkan estimasi harga dan timeline.</li><li><strong>Persetujuan</strong> — desain dan sample disetujui, DP dibayarkan.</li><li><strong>Produksi</strong> — pengerjaan dimulai dan progress dapat dilacak melalui halaman tracking.</li><li><strong>Quality Check</strong> — setiap produk diperiksa sebelum dikemas.</li><li><strong>Pengiriman</strong> — pesanan dikirim atau diambil sesuai kesepakatan.</li></ol><p>Nomor pesanan (contoh: ZDK-0001) dapat digunakan untuk memantau progress produksi kapan saja di halaman tracking.</p>',
            ],
        ];
        $admin = $admin ?? User::first();
        foreach ($articles as $i => $a) {
            Article::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($a['title'])],
                [
                    'title' => $a['title'],
                    'excerpt' => $a['excerpt'],
                    'content' => $a['content'],
                    'article_category_id' => $catTips?->id,
                    'user_id' => $admin?->id,
                    'is_featured' => $i === 0,
                    'published_at' => now()->subDays(10 - $i * 3),
                ]
            );
        }
    }

    private function seedReviews(): void
    {
        if (Review::exists()) {
            return;
        }

        $reviews = [
            ['author_name' => 'Budi Santoso', 'rating' => 5, 'days_ago' => 12, 'content' => 'Pesan 150 polo shirt untuk seragam kantor. Hasil bordir rapi, bahan sesuai sample, dan selesai tepat waktu. Enaknya bisa pantau progress produksi lewat nomor pesanan. Recommended!'],
            ['author_name' => 'Siti Rahmawati', 'rating' => 5, 'days_ago' => 28, 'content' => 'Sudah dua kali produksi seragam sekolah di sini. Jahitan kuat, ukuran konsisten, dan komunikasinya enak via WhatsApp. Terima kasih Zada Karya!'],
            ['author_name' => 'Andi Pratama', 'rating' => 5, 'days_ago' => 45, 'content' => 'Bikin jersey futsal 8 tim, printing sublim warnanya tajam banget dan tidak luntur setelah dicuci berkali-kali. Prosesnya jelas dari desain sampai kirim.'],
            ['author_name' => 'Dewi Lestari', 'rating' => 4, 'days_ago' => 60, 'content' => 'Kaos sablon untuk event komunitas hasilnya bagus, sablonnya tebal dan rapi. Pengerjaan sedikit lebih lama dari estimasi tapi diinformasikan dengan baik.'],
            ['author_name' => 'Rudi Hartono', 'rating' => 5, 'days_ago' => 90, 'content' => 'Seragam kerja lapangan bahan drill-nya kuat dan nyaman. Sudah langganan untuk kebutuhan seragam tahunan perusahaan. Pelayanan konsultasinya membantu sekali.'],
        ];

        foreach ($reviews as $i => $review) {
            Review::create([
                'author_name' => $review['author_name'],
                'rating' => $review['rating'],
                'content' => $review['content'],
                'review_date' => now()->subDays($review['days_ago'])->toDateString(),
                'sort_order' => $i,
            ]);
        }
    }
}
