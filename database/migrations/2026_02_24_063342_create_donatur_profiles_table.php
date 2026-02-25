<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donatur_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_lengkap');
            $table->string('institusi')->nullable(); // Yayasan, PT, atau Kosong jika Hamba Allah
            $table->string('no_hp')->nullable();
            $table->decimal('total_donasi', 15, 2)->default(0); // Total dana yang sudah didonasikan
            $table->enum('status_kemitraan', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donatur_profiles');
    }
};