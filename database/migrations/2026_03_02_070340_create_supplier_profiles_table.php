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
        Schema::create('supplier_profiles', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel users (Pemasok adalah User dengan role tertentu)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Status Alur Approval
            // Default: 'belum_melengkapi'
            // Pilihan: 'belum_melengkapi', 'menunggu_review', 'disetujui', 'ditolak'
            $table->string('status_verifikasi')->default('belum_melengkapi');

            // Data Profil Usaha / Grosir
            $table->string('nama_usaha')->nullable();
            $table->string('nama_pemilik')->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat_gudang')->nullable();

            // Data Keuangan (Untuk pencairan dana dari LKBB)
            $table->string('info_rekening')->nullable(); 

            // Dokumen (Menyimpan path file)
            $table->string('foto_ktp')->nullable();
            $table->string('foto_usaha')->nullable();

            // Fitur Tambahan: Catatan jika ditolak oleh LKBB
            $table->text('catatan_penolakan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_profiles');
    }
};