<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('merchant_products', function (Blueprint $table) {
            // Ubah nama kolom harga menjadi harga_jual
            $table->renameColumn('harga', 'harga_jual');
            
            // Tambah Harga Pokok (Modal) dan Foto
            $table->decimal('harga_pokok', 12, 2)->after('kategori')->default(0);
            $table->string('foto_produk')->nullable()->after('nama_produk');
        });
    }

    public function down(): void {
        Schema::table('merchant_products', function (Blueprint $table) {
            $table->renameColumn('harga_jual', 'harga');
            $table->dropColumn(['harga_pokok', 'foto_produk']);
        });
    }
};