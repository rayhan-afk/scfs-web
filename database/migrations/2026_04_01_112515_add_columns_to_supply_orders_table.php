<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            // Tambahkan nullable() dulu agar data lama yang belum punya pemasok_id tidak error
            $table->foreignId('pemasok_id')->nullable()->after('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('status_pembiayaan')->default('siap_diajukan')->after('status');
            $table->string('id_pengajuan')->nullable()->after('status_pembiayaan');
        });
    }

    public function down(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->dropForeign(['pemasok_id']);
            $table->dropColumn(['pemasok_id', 'status_pembiayaan', 'id_pengajuan']);
        });
    }
};