<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahan kolom untuk Mahasiswa
            $table->string('nim')->nullable()->unique()->after('email');
            $table->string('jurusan')->nullable()->after('nim');
            $table->string('ktm_image')->nullable()->after('jurusan'); // Path foto KTM
            
            // Status: 'menunggu', 'disetujui', 'ditolak'
            $table->enum('status_verifikasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu')->after('password');
            
            // Role: admin, mahasiswa, merchant (biar jelas)
            // (Kalau kolom role sudah ada sebelumnya, hapus baris ini)
            // $table->string('role')->default('mahasiswa')->after('id'); 
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nim', 'jurusan', 'ktm_image', 'status_verifikasi']);
        });
    }
};