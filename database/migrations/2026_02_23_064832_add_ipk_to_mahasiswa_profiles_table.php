<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            // Kita pakai decimal(3,2) biar formatnya pas, contoh: 3.85 atau 4.00
            $table->decimal('ipk', 3, 2)->nullable()->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table) {
            $table->dropColumn('ipk');
        });
    }
};