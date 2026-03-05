<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pencairan')->unique();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            
            // Pencatatan Settlement (Audit Trail)
            $table->decimal('nominal_kotor', 15, 2); // Saldo token yang ditarik
            $table->decimal('potongan_lkbb', 15, 2); // Tagihan hutang yang dilunasi
            $table->decimal('nominal_bersih', 15, 2); // Uang yang wajib ditransfer LKBB
            
            $table->string('info_pencairan'); // Tujuan transfer (GoPay/Bank)
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->text('catatan_lkbb')->nullable(); // Alasan jika ditolak
            
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('withdrawals'); }
};