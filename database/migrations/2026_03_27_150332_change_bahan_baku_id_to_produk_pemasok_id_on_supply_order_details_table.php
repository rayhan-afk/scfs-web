<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supply_order_details', function (Blueprint $table) {
            // 1. Hapus relasi (foreign key) dan kolom bahan_baku_id yang lama
            $table->dropForeign(['bahan_baku_id']);
            $table->dropColumn('bahan_baku_id');

            // 2. Tambahkan kolom produk_pemasok_id yang baru beserta relasinya
            $table->foreignId('produk_pemasok_id')
                  ->nullable()
                  ->after('supply_order_id')
                  ->constrained('produk_pemasoks')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_order_details', function (Blueprint $table) {
            // Jika di-rollback, kembalikan ke bahan_baku_id
            $table->dropForeign(['produk_pemasok_id']);
            $table->dropColumn('produk_pemasok_id');

            $table->foreignId('bahan_baku_id')
                  ->nullable()
                  ->after('supply_order_id')
                  ->constrained('bahan_bakus')
                  ->nullOnDelete();
        });
    }
};