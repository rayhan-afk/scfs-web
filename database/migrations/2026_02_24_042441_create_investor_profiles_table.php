<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_lengkap');
            $table->string('perusahaan')->nullable(); // Jika mewakili institusi
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('info_bank')->nullable(); // Tujuan transfer bagi hasil
            $table->decimal('total_investasi_aktif', 15, 2)->default(0); 
            $table->decimal('total_bagi_hasil', 15, 2)->default(0);
            $table->enum('status_kemitraan', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_profiles');
    }
};