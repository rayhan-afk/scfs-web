<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. HAPUS TABEL HANTU (Yang sudah tidak dipakai)
        Schema::dropIfExists('supply_chains');
        
        // Catatan: 'bahan_bakus' biarkan dulu jika masih di-referensikan tabel lain, 
        // tapi nanti kita tinggalkan.

        // 2. REVISI TABEL PRODUK PEMASOK (Pisah Modal & Margin)
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            // Hapus harga_grosir lama
            $table->dropColumn('harga_grosir');
            
            // Tambahkan harga modal (untuk LKBB) & margin (untuk Pemasok)
            $table->decimal('harga_modal', 15, 2)->after('deskripsi')->default(0);
            $table->decimal('margin_pemasok', 15, 2)->after('harga_modal')->default(0);
        });

        // 3. REVISI TABEL MERCHANT PROFILE (Perjelas Bagi Hasil)
        Schema::table('merchant_profiles', function (Blueprint $table) {
            // Kita ubah nama kolom persentase agar lebih spesifik
            $table->renameColumn('persentase_bagi_hasil', 'persentase_fee_merchant');
            
            // Note: tagihan_setoran_tunai TETAP ADA karena ternyata merchant nerima cash
        });

        // 4. REVISI TABEL WITHDRAWALS (Hapus Potongan Ganda)
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn('potongan_lkbb');
        });

        // 5. REVISI SUPPLY ORDERS (Tambahkan Pemasok ID)
        Schema::table('supply_orders', function (Blueprint $table) {
            // Pastikan pemasok_id belum ada constraint yang salah sebelumnya
            // Jika sudah ada pemasok_id dari migration Anda, bagian ini bisa dilewati
            // Namun melihat dump Anda, kolom ini sudah ada, jadi aman.
        });
        
        // 6. REVISI SUPPLY ORDER DETAILS (Arahkan ke produk_pemasok)
        Schema::table('supply_order_details', function (Blueprint $table) {
            // Hapus snapshot satuan karena subtotal sudah cukup
            $table->dropColumn(['nama_bahan_snapshot', 'harga_satuan_snapshot', 'satuan_snapshot']);
            
            // Tambah snapshot baru yang memisahkan modal dan margin
            $table->string('nama_produk_snapshot')->after('produk_pemasok_id');
            $table->decimal('harga_modal_snapshot', 15, 2)->after('nama_produk_snapshot');
            $table->decimal('margin_pemasok_snapshot', 15, 2)->after('harga_modal_snapshot');
        });
    }

    public function down(): void
    {
        // ... (Fungsi rollback jika diperlukan)
    }
};