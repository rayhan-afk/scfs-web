<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_opname_pemasoks', function (Blueprint $table) {
            $table->id();
            // Relasi ke produk yang sedang di-opname
            $table->foreignId('produk_pemasok_id')->constrained('produk_pemasoks')->onDelete('cascade');
            
            $table->integer('stok_sistem'); // Jumlah di aplikasi sebelum hitung fisik
            $table->integer('stok_fisik');  // Jumlah nyata di gudang
            $table->integer('selisih');     // fisik - sistem
            $table->string('keterangan')->nullable(); // Alasan (misal: barang pecah)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_opname_pemasoks');
    }
};