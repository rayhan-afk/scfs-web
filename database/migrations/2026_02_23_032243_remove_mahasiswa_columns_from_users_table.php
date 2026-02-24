<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kita buang kolom-kolom ini dari tabel users
            $table->dropColumn([
                'nim', 
                'jurusan', 
                'ktm_image', 
                'status_verifikasi', 
                'status_bantuan', 
                'saldo'
            ]);
        });
    }

    public function down(): void
    {
        // Biarkan kosong atau tambahkan ulang jika mau rollback
    }
};