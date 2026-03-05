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
    Schema::create('supply_orders', function (Blueprint $table) {
        $table->id();
        $table->string('nomor_order')->unique();
        $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
        $table->decimal('total_estimasi', 15, 2);
        $table->date('tanggal_kebutuhan'); // Kapan barang ini mau dipakai/dikirim?
        $table->text('catatan')->nullable();
        $table->enum('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim', 'selesai', 'ditolak'])->default('menunggu_lkbb');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_orders');
    }
};
