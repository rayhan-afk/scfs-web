<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Pembeli (Mahasiswa)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('order_id')->unique(); // TRX-2026xxxx
            $table->decimal('total_amount', 15, 2);
            
            // Status Transaksi
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            
            // Tipe Transaksi (Beli Makan, Topup Donasi, Bayar Pemasok)
            $table->string('type')->default('purchase');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
