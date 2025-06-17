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
        Schema::create('companie_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('companie_id')->constrained('companies')->onDelete('cascade');
            $table->string('image_path'); // Menyimpan path ke gambar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companie_backgrounds');
    }
};
