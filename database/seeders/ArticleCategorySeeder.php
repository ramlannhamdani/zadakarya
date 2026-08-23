<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Tips & Panduan', 'Informasi Produksi', 'Berita'] as $name) {
            ArticleCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
