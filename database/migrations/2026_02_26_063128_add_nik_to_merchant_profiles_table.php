<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->after('nama_pemilik');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->dropColumn('nik');
        });
    }
};