<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('convection_assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->string('category')->default('machinery');
            $table->date('purchase_date')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit')->default('unit');
            $table->unsignedBigInteger('purchase_price')->default(0);
            $table->unsignedBigInteger('current_value')->default(0);
            $table->string('condition')->default('good');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convection_assets');
    }
};
