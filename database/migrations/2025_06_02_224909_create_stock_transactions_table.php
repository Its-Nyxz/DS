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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('related_transaction_id')->nullable()->constrained('stock_transactions')->onDelete('set null');
            $table->string('transaction_code')->unique();
            $table->dateTime('transaction_date')->nullable();
            $table->enum('type', ['in', 'out', 'adjustment', 'retur_in', 'retur_out']);
            $table->enum('payment_type', ['cash', 'term'])->default('cash')->nullable(); // Menambahkan jenis pembayaran
            $table->enum('difference_reason', ['damaged', 'stolen', 'clerical_error', 'other'])->nullable(); // Alasan perbedaan
            $table->enum('opname_type', ['regular', 'audit', 'ad_hoc'])->nullable(); // Jenis opname
            $table->boolean('is_approved')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->boolean('is_fully_paid')->default(false);
            $table->date('fully_paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
