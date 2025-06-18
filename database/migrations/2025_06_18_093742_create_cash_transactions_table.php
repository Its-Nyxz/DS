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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['stock', 'expense', 'payment']); // Jenis transaksi: stok, pengeluaran, atau pembayaran
            $table->foreignId('stock_transaction_id')->nullable()->constrained('stock_transactions')->onDelete('cascade'); // ID transaksi stok terkait, nullable jika bukan transaksi stok
            $table->decimal('amount', 12, 2); // Jumlah uang yang terlibat dalam transaksi kas
            $table->date('transaction_date'); // Tanggal transaksi
            $table->string('payment_method'); // Metode pembayaran (cash, transfer, dll)
            $table->string('reference_number')->nullable(); // Nomor referensi transaksi (misal nomor bukti bayar)
            $table->text('note')->nullable(); // Catatan tambahan tentang transaksi
            $table->enum('debt_credit', ['utang', 'piutang'])->nullable(); // Kolom untuk mencatat apakah ini utang atau piutang
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
