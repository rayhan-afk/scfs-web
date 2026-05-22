<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_returns', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke order supply chain kamu
            $table->foreignId('supply_order_id')->constrained('supply_orders')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            
            // Input dari Merchant
            $table->string('alasan'); // Contoh: Barang Rusak, Basi, Kurang Jumlah, Tidak Sesuai
            $table->text('deskripsi_masalah');
            $table->string('foto_bukti')->nullable(); // Path foto yang di-upload
            $table->enum('solusi_diajukan', ['refund', 'kirim_ulang']); // Solusi yang diminta merchant
            
            // Status Alur Return
            $table->enum('status', [
                'pending',          // Menunggu review Pemasok
                'disetujui',        // Disetujui Pemasok (Selesai)
                'ditolak',          // Ditolak Pemasok
                'banding_lkbb',     // Merchant tidak terima ditolak, masuk ke LKBB (Sengketa)
                'selesai_lkbb'      // Selesai setelah ditengahi LKBB
            ])->default('pending');
            
            // Catatan Evaluasi
            $table->text('catatan_pemasok')->nullable(); // Alasan jika pemasok menolak / memberi catatan
            $table->text('catatan_lkbb')->nullable();    // Keputusan final dari pihak LKBB jika sengketa
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_returns');
    }
};