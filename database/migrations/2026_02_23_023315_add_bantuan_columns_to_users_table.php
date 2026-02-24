<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Status pengajuan saldo ke LKBB
            $table->enum('status_bantuan', ['belum_diajukan', 'diajukan', 'disetujui', 'ditolak'])->default('belum_diajukan')->after('status_verifikasi');
            
            // Kolom saldo digital (Pakai decimal untuk uang/token)
            $table->decimal('saldo', 15, 2)->default(0)->after('status_bantuan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status_bantuan', 'saldo']);
        });
    }
};