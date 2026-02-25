<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donatur_profiles', function (Blueprint $table) {
            $table->string('rekening_sumber')->nullable()->after('no_hp'); // Untuk lacak mutasi masuk
            $table->text('alamat')->nullable()->after('rekening_sumber'); // Untuk kirim sertifikat/laporan
            $table->enum('tipe_donatur', ['rutin', 'insidental'])->default('insidental')->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('donatur_profiles', function (Blueprint $table) {
            $table->dropColumn(['rekening_sumber', 'alamat', 'tipe_donatur']);
        });
    }
};