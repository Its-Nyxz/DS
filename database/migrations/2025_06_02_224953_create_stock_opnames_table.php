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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')->nullable()->constrained('stock_transactions'); // Relasi dengan transaksi stok
            $table->foreignId('item_id')->constrained('items'); // Referensi ke produk
            $table->integer('actual_stock'); // Stok fisik
            $table->integer('system_stock'); // Stok dalam sistem
            $table->integer('difference'); // Perbedaan stok
            $table->enum('status', ['tambah', 'penyusutan', 'sesuai']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
