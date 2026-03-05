<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('setoran_tunais', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_setoran')->unique();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->enum('status', ['menunggu_penjemputan', 'selesai'])->default('menunggu_penjemputan');
            $table->string('nama_petugas')->nullable(); // Nama petugas yang mengambil uang
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('setoran_tunais'); }
};