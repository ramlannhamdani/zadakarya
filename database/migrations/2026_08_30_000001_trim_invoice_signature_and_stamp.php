<?php

use App\Models\Setting;
use App\Support\ImageUploader;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Tanda tangan & stempel yang diunggah sebelum fitur auto-trim ada masih
     * membawa bingkai transparan, sehingga tampil kecil di PDF. Dipotong sekali
     * di sini supaya tidak perlu diunggah ulang. Aman diulang: gambar yang sudah
     * rapat tidak diubah.
     */
    public function up(): void
    {
        foreach (['invoice_signature', 'invoice_stamp'] as $key) {
            $path = Setting::get($key);

            if (! $path || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            ImageUploader::trimTransparent(Storage::disk('public')->path($path));
        }
    }

    public function down(): void
    {
        // Pemotongan tidak dapat dikembalikan; unggah ulang bila perlu.
    }
};
