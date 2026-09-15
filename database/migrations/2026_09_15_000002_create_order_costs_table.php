<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40)->index();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('receipt_path')->nullable();
            $table->date('spent_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_costs');
    }
};
