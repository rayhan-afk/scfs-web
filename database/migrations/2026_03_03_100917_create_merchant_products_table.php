<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchant_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('nama_produk');
            $table->enum('kategori', ['makanan', 'minuman', 'barang_koperasi', 'lainnya'])->default('makanan');
            $table->decimal('harga', 12, 2);
            $table->boolean('is_tersedia')->default(true); // Toggle Habis/Ready
            
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('merchant_products'); }
};