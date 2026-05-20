<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); // Contoh: ORD-20260519-XYZ
            $table->foreignId('mahasiswa_id')->constrained('users')->onDelete('cascade'); // Pembeli
            $table->foreignId('merchant_id')->constrained('users')->onDelete('cascade');  // Kantin
            
            $table->decimal('total_amount', 12, 2);
            $table->text('catatan_pembeli')->nullable(); // Misal: "Pedes bang, jangan pakai bawang"
            
            // STATUS ALUR DAPUR
            $table->enum('status', [
                'menunggu_konfirmasi', // Baru dipesan mhs
                'diproses',            // Sedang dimasak kantin
                'siap_diambil',        // Udah dibungkus
                'selesai',             // Udah diserahkan ke mhs
                'dibatalkan'           // Ditolak kantin (misal kehabisan bahan)
            ])->default('menunggu_konfirmasi');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_orders');
    }
};