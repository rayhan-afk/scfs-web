<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_order_id')->constrained('online_orders')->onDelete('cascade');
            $table->foreignId('merchant_product_id')->nullable()->constrained('merchant_products')->nullOnDelete();
            
            // Snapshot harga & nama (jaga-jaga kalau menu dihapus/diganti harga)
            $table->string('nama_produk_snapshot');
            $table->decimal('harga_pokok_snapshot', 12, 2);
            $table->decimal('harga_jual_snapshot', 12, 2);
            $table->integer('qty');
            $table->decimal('subtotal', 12, 2);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_order_items');
    }
};