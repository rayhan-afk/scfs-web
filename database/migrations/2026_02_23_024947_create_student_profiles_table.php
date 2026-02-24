<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Detail Mahasiswa
            $table->string('nim'); // Nomor Induk Mahasiswa
            $table->string('university_name')->default('Universitas Mitra');
            $table->string('faculty')->nullable();
            
            // Status Approval & Dokumen
            $table->enum('status_verifikasi', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('dokumen_ktm')->nullable(); // Foto Kartu Tanda Mahasiswa
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};