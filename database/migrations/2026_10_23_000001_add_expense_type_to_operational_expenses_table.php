<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_expenses', function (Blueprint $table) {
            $table->string('expense_type', 30)->default('convection')->after('category')->index();
        });
    }

    public function down(): void
    {
        Schema::table('operational_expenses', function (Blueprint $table) {
            $table->dropColumn('expense_type');
        });
    }
};
