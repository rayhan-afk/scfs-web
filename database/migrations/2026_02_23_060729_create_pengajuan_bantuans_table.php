<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_bantuans', function (Blueprint $table) {
            $table->id();
            // Relasi ke profil mahasiswa
            $table->foreignId('mahasiswa_profile_id')->constrained()->onDelete('cascade');

            $table->decimal('nominal', 15, 2); // Jumlah yang diajukan (misal Rp 500.000)
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak'])->default('diajukan');
            $table->string('nomor_pengajuan')->unique(); // Untuk ID Pengajuan (misal: SC-2026-001)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_bantuans');
    }
};