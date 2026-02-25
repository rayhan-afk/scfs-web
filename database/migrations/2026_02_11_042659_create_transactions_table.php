<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique(); // Nomor resi, cth: TRX-260224-001
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Pemilik mutasi ini
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete(); // Pihak lawan (opsional)
            
            $table->enum('type', ['kredit', 'debit']); // KREDIT = Uang Masuk, DEBIT = Uang Keluar
            
            // Kategori transaksi
            $table->string('category'); // cth: donasi_masuk, beli_makan, pencairan_merchant, bayar_po, dll
            
            $table->decimal('amount', 15, 2); // Nominal transaksi
            $table->string('description')->nullable(); // Keterangan transaksi
            
            $table->enum('status', ['pending', 'success', 'failed'])->default('success');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};