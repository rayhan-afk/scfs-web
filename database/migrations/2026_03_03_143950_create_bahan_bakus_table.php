<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void {
        Schema::create('bahan_bakus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan');
            $table->string('kategori')->default('umum'); // Sembako, Sayur, Daging, dll
            $table->string('satuan'); // kg, liter, dus, ikat
            $table->decimal('harga_estimasi', 12, 2);
            $table->string('foto_bahan')->nullable();
            $table->boolean('is_tersedia')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_bakus');
    }
};
