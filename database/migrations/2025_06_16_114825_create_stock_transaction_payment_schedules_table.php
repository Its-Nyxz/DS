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
        Schema::create('stock_transaction_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')
                ->constrained('stock_transactions')
                ->cascadeOnDelete();

            $table->decimal('scheduled_amount', 12, 2); // Jumlah yang harus dibayar pada termin ini
            $table->date('due_date'); // Jatuh tempo termin
            $table->boolean('is_paid')->default(false); // Sudah dibayar atau belum
            $table->date('paid_at')->nullable(); // Tanggal pelunasan termin ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transaction_payment_schedules');
    }
};
