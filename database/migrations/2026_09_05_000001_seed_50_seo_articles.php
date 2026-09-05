<?php

use Database\Seeders\ArticleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jalankan seeder otomatis saat 'php artisan migrate' dijalankan di server hosting
        $seeder = new ArticleSeeder();
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opsional: tidak perlu menghapus artikel agar data aman
    }
};
