<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Snapshot keranjang [{product_id, qty, harga_jual, harga_pokok}, ...]
            // Dipakai job auto-expire untuk restore stok pada transaksi QR pending kadaluwarsa.
            $table->json('cart_snapshot')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('cart_snapshot');
        });
    }
};
