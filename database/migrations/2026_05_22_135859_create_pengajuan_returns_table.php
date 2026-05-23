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

            // Relasi ke order utama (cascade delete bila order dihapus)
            $table->foreignId('supply_order_id')->constrained('supply_orders')->cascadeOnDelete();

            // Optional item-level return (NULL = whole order)
            $table->foreignId('supply_order_detail_id')->nullable()
                ->constrained('supply_order_details')->nullOnDelete();

            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();

            // Input merchant
            $table->enum('tipe_masalah', [
                'rusak',
                'basi',
                'kurang_qty',
                'salah_barang',
                'kualitas_buruk',
                'terlambat',
            ]);
            $table->unsignedInteger('qty_bermasalah')->default(1);
            $table->text('deskripsi_masalah');
            $table->json('foto_bukti')->nullable();       // array of storage paths
            $table->string('video_bukti')->nullable();    // single video path
            $table->enum('solusi_diajukan', [
                'refund',
                'kirim_ulang',
                'ganti_barang',
                'partial_refund',
            ]);

            // Lifecycle (5 status — clean, no ambiguity)
            $table->enum('status', [
                'pending_supplier_review',
                'approved',
                'rejected',
                'escalated_lkbb',
                'resolved',
            ])->default('pending_supplier_review');

            // Keputusan final pemasok/LKBB (label terpisah dari status)
            $table->string('keputusan_resolusi')->nullable();
            // Untuk pemasok: refund | kirim_ulang | ganti_barang | partial_refund
            // Untuk LKBB (saat resolved): menangkan_merchant_refund | menangkan_merchant_replace | menangkan_pemasok

            // Catatan dari reviewer
            $table->text('catatan_pemasok')->nullable();
            $table->text('catatan_lkbb')->nullable();

            // Anti-abuse + audit
            $table->timestamp('deadline_at')->nullable();  // deadline pemasok response (default +48h dari created_at)
            $table->json('riwayat_audit')->nullable();     // append-only event log
            $table->boolean('flag_fraud')->default(false); // ditandai bila pattern abnormal

            $table->timestamps();

            $table->index('status');
            $table->index('merchant_id');
            $table->index('supplier_id');
            $table->index(['supply_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_returns');
    }
};
