<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            // Menambahkan kolom status_verifikasi dengan default 'pending'
            $table->string('status_verifikasi')->default('pending')->after('id'); 
        });
    }

    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            // Menghapus kolom jika di-rollback
            $table->dropColumn('status_verifikasi');
        });
    }
};