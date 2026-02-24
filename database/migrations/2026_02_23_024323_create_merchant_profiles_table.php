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
            // Relasi ke tabel users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Detail Bisnis Merchant
            $table->string('company_name'); // Nama Toko / Perusahaan
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Kolom Persetujuan LKBB
            $table->enum('status_verifikasi', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('credit_limit', 15, 2)->default(0); // Limit pembiayaan
            
            // Berkas Dokumen (Menyimpan path/URL file)
            $table->string('dokumen_ktp')->nullable();
            $table->string('dokumen_nib')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_profiles');
    }
};