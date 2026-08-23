<?php

namespace Database\Seeders;

use App\Models\PortfolioCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PortfolioCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Seragam', 'Kantor', 'Sekolah', 'Olahraga', 'Polo', 'Kaos', 'Celana', 'Custom'] as $name) {
            PortfolioCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
