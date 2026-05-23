<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * - harga_modal: decimal(15,2) -> unsignedBigInteger (rupiah bulat, buang sufiks .00
     *   yang memicu bug mask Rupiah x100).
     * - margin_pemasok (rupiah) -> margin_persen (persen dari modal).
     */
    public function up(): void
    {
        // 1. Tambah kolom margin_persen
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->decimal('margin_persen', 5, 2)->default(0)->after('harga_modal');
        });

        // 2. Backfill: konversi margin rupiah lama -> persen dari modal
        DB::statement('
            UPDATE produk_pemasoks
            SET margin_persen = ROUND(margin_pemasok / NULLIF(harga_modal, 0) * 100, 2)
            WHERE harga_modal > 0
        ');

        // 3. Drop kolom margin_pemasok lama (rupiah)
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->dropColumn('margin_pemasok');
        });

        // 4. harga_modal jadi integer rupiah bulat
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->unsignedBigInteger('harga_modal')->default(0)->change();
        });
    }

    /**
     * Tidak sepenuhnya reversibel: konversi rupiah->persen menghilangkan nilai
     * rupiah margin asli. down() hanya mengembalikan bentuk kolom.
     */
    public function down(): void
    {
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->decimal('harga_modal', 15, 2)->default(0)->change();
        });
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->decimal('margin_pemasok', 15, 2)->default(0)->after('harga_modal');
        });
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->dropColumn('margin_persen');
        });
    }
};
