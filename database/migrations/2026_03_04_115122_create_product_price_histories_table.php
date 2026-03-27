<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_product_id')->constrained('merchant_products')->cascadeOnDelete();
            $table->decimal('harga_pokok_lama', 12, 2)->nullable();
            $table->decimal('harga_pokok_baru', 12, 2);
            $table->decimal('harga_jual_lama', 12, 2)->nullable();
            $table->decimal('harga_jual_baru', 12, 2);
            $table->timestamps(); // Kapan diubahnya
        });
    }
};