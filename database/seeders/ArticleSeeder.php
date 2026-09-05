<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan kategori-kategori utama tersedia
        $categories = [
            'Tips & Panduan' => 'tips-panduan',
            'Informasi Produksi' => 'informasi-produksi',
            'Panduan Bahan & Kain' => 'panduan-bahan-kain',
            'Inspirasi & Bisnis' => 'inspirasi-bisnis',
            'Berita' => 'berita',
        ];

        $categoryMap = [];
        foreach ($categories as $name => $slug) {
            $cat = ArticleCategory::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
            $categoryMap[$slug] = $cat->id;
        }

        // 2. Ambil author default
        $author = User::first();
        $authorId = $author?->id;

        // 3. Load data 50 artikel
        $articlesPart1 = require __DIR__ . '/data/articles_1_25.php';
        $articlesPart2 = require __DIR__ . '/data/articles_26_50.php';
        $articles = array_merge($articlesPart1, $articlesPart2);

        // 4. Seeding data artikel secara idempotent (updateOrCreate)
        $count = 0;
        foreach ($articles as $index => $item) {
            $catSlug = $item['category_slug'] ?? 'tips-panduan';
            $catId = $categoryMap[$catSlug] ?? ($categoryMap['tips-panduan'] ?? null);

            Article::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'] ?? Str::limit(strip_tags($item['content']), 250),
                    'content' => trim($item['content']),
                    'article_category_id' => $catId,
                    'tags' => $item['tags'] ?? [],
                    'user_id' => $authorId,
                    'is_featured' => $index < 3, // Featured untuk 3 artikel pertama
                    'published_at' => $item['published_at'],
                    'seo_title' => $item['seo_title'] ?? $item['title'],
                    'seo_description' => $item['seo_description'] ?? ($item['excerpt'] ?? null),
                ]
            );
            $count++;
        }

        $this->command?->info("Berhasil melakukan seeding {$count} artikel SEO Zada Karya Production.");
    }
}
