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
        Schema::create('stock_transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')
                ->constrained('stock_transactions')
                ->cascadeOnDelete();

            $table->foreignId('payment_schedule_id')
                ->nullable()
                ->constrained('stock_transaction_payment_schedules')
                ->nullOnDelete();

            $table->decimal('amount', 12, 2); // Jumlah pembayaran
            $table->date('payment_date'); // Tanggal pembayaran
            $table->string('payment_method')->nullable(); // cash, transfer, dll
            $table->string('reference_number')->nullable(); // Bukti bayar
            $table->text('note')->nullable(); // Catatan tambahan

            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transaction_payments');
    }
};
