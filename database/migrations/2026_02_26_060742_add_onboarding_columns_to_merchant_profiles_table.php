<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            // Menambahkan kolom status dan dokumen
            $table->string('status_verifikasi')->default('belum_melengkapi')->after('nama_pemilik');
            $table->string('foto_ktp')->nullable()->after('status_verifikasi');
            $table->string('foto_kantin')->nullable()->after('foto_ktp');
            $table->text('catatan_penolakan')->nullable()->after('foto_kantin');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            // Menghapus kolom jika di-rollback
            $table->dropColumn([
                'status_verifikasi', 
                'foto_ktp', 
                'foto_kantin', 
                'catatan_penolakan'
            ]);
        });
    }
};