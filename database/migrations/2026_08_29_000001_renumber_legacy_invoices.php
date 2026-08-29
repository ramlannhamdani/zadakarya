<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Invoice lama bernomor INV-XXXX diselaraskan dengan nomor pesanannya
     * (ZDK-XXXX-HHMMTT; invoice tambahan -2, -3). Aman dijalankan ulang.
     */
    public function up(): void
    {
        Invoice::renumberLegacy();
    }

    public function down(): void
    {
        // Nomor lama tidak dikembalikan.
    }
};
