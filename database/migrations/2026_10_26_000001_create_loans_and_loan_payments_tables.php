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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->string('lender_name');
            $table->unsignedBigInteger('amount');
            $table->date('loan_date');
            $table->date('due_date')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('unpaid'); // unpaid, partial, paid
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->string('reference_no')->nullable()->unique();
            $table->date('payment_date');
            $table->unsignedBigInteger('amount');
            $table->string('payment_method')->default('transfer');
            $table->string('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('loans');
    }
};
