<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_chains', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            
            // Relasi ke tabel users
            $table->foreignId('merchant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            
            // Detail Pesanan
            $table->text('item_description');
            
            // Perhitungan Keuangan
            $table->decimal('capital_amount', 15, 2); // Modal dari LKBB ke Pemasok
            $table->decimal('margin_amount', 15, 2);  // Profit LKBB
            $table->decimal('total_amount', 15, 2);   // Tagihan ke Merchant (Capital + Margin)
            
            // Status Tracking Barang & Pengajuan
            $table->enum('status', [
                'PENDING',       // Menunggu persetujuan Admin
                'APPROVED',      // Disetujui Admin
                'FUNDED',        // Dana sudah cair ke Pemasok
                'IN_PRODUCTION', // Barang sedang dibuat
                'DELIVERING',    // Barang dikirim ke Merchant
                'RECEIVED',      // Barang diterima Merchant
                'COMPLETED',     // Selesai (Sudah lunas)
                'REJECTED'       // Ditolak Admin
            ])->default('PENDING');

            // Status Pembayaran Merchant
            $table->enum('payment_status', ['UNPAID', 'PARTIAL', 'PAID'])->default('UNPAID');
            
            $table->date('due_date')->nullable(); // Jatuh tempo pembayaran
            
            $table->timestamps();
            $table->softDeletes(); // Aman jika tidak sengaja terhapus
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_chains');
    }
};