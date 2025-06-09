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
        Schema::create('stock_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')->nullable()->constrained('stock_transactions');
            $table->foreignId('item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('item_supplier_id')->nullable()->constrained('item_suppliers');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('selected_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transaction_items');
    }
};
