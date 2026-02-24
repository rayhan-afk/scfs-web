<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_profiles', function (Blueprint $table) {
            $table->id();
            // Sambungan ke tabel users (akun loginnya)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Detail Profil Kantin
            $table->string('nama_kantin');
            $table->string('nama_pemilik');
            $table->string('lokasi_blok')->nullable(); // Misal: Blok A, Kantin Timur, dll
            
            // Pengaturan Keuangan
            $table->integer('persentase_bagi_hasil')->default(10); // Misal LKBB ambil 10%
            $table->decimal('tagihan_setoran_tunai', 15, 2)->default(0); // Hutang cash dari pembeli umum
            $table->decimal('saldo_token', 15, 2)->default(0); // Token digital yang belum dicairkan
            
            $table->enum('status_toko', ['buka', 'tutup'])->default('tutup');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_profiles');
    }
};