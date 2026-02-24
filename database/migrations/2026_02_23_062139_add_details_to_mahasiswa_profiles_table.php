<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            $table->string('no_hp')->nullable()->after('jurusan');
            $table->text('alamat')->nullable()->after('no_hp');
            $table->string('semester')->nullable()->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            $table->dropColumn(['no_hp', 'alamat', 'semester']);
        });
    }
};