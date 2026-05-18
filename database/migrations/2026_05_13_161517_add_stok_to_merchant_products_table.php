<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_products', function (Blueprint $table) {
            // Tambahkan kolom stok, default 0
            $table->integer('stok')->default(0)->after('is_tersedia');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_products', function (Blueprint $table) {
            // Rollback jika diperlukan
            $table->dropColumn('stok');
        });
    }
};