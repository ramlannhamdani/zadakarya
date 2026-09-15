<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('order_costs', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
