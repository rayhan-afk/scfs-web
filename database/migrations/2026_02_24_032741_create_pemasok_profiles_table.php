<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemasok_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Identitas usaha
            $table->string('nama_perusahaan')->nullable();
            $table->string('kategori_barang')->default('Lainnya');
            $table->string('nama_pic')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();

            // Lifecycle + dokumen (mirror MerchantProfile)
            $table->string('status_verifikasi')->default('belum_melengkapi');
            $table->string('foto_ktp')->nullable();
            $table->string('foto_gudang')->nullable();
            $table->text('catatan_penolakan')->nullable();

            // Rekening flat (mirror Merchant: nama_bank + no_rekening)
            $table->string('nama_bank')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('atas_nama_rekening')->nullable();

            // Operasional + keuangan
            $table->enum('status_kemitraan', ['aktif', 'nonaktif'])->default('nonaktif');
            $table->enum('status_operasional', ['buka', 'tutup'])->default('tutup');
            $table->decimal('saldo_pendapatan', 15, 2)->default(0);
            $table->decimal('tagihan_berjalan', 15, 2)->default(0);

            $table->timestamps();

            $table->index('status_verifikasi');
            $table->index('status_kemitraan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemasok_profiles');
    }
};
