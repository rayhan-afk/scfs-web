<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
    Schema::create('supply_order_details', function (Blueprint $table) {
        $table->id();
        $table->foreignId('supply_order_id')->constrained('supply_orders')->cascadeOnDelete();
        $table->foreignId('bahan_baku_id')->nullable()->constrained('bahan_bakus')->nullOnDelete();
        
        // SNAPSHOT DATA (Sangat Penting!)
        $table->string('nama_bahan_snapshot');
        $table->decimal('harga_satuan_snapshot', 12, 2);
        $table->integer('qty');
        $table->string('satuan_snapshot');
        $table->decimal('subtotal', 15, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_order_details');
    }
};
