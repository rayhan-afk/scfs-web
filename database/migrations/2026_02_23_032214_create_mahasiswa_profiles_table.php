<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa_profiles', function (Blueprint $table) {
            $table->id();
            // Sambungan ke tabel users (Jika user dihapus, profil ini otomatis terhapus / cascade)
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            // Pindahan dari tabel users sebelumnya
            $table->string('nim')->nullable()->unique();
            $table->string('jurusan')->nullable();
            $table->string('ktm_image')->nullable();
            $table->enum('status_verifikasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->enum('status_bantuan', ['belum_diajukan', 'diajukan', 'disetujui', 'ditolak'])->nullable();
            $table->decimal('saldo', 15, 2)->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_profiles');
    }
};