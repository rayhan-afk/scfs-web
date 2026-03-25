<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_pemasoks', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel users (Pemasok)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('sku')->unique(); // Kode unik barang
            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga_grosir', 15, 2); 
            $table->integer('stok_sekarang')->default(0);
            $table->integer('batas_minimum_stok')->default(5); // Untuk alert restock
            $table->string('foto_produk')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            
            $table->timestamps();
            $table->softDeletes(); // Keamanan agar data tidak hilang permanen
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_pemasoks');
    }
};