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
        Schema::create('returs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
        $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->enum('type', ['supplier', 'customer']);
            $table->enum('direction', ['in', 'out']);
            $table->integer('quantity');
            $table->string('reference')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returs');
    }
};
