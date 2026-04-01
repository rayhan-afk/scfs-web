<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi_pemasoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Relasi ke Pemasok
            $table->string('kode_batch')->unique();
            $table->string('nama_produk');
            $table->integer('jumlah');
            $table->string('satuan')->nullable(); 
            $table->dateTime('waktu_produksi');
            $table->string('penanggung_jawab')->nullable();
            $table->enum('status', ['selesai', 'gagal'])->default('selesai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_pemasoks');
    }
};