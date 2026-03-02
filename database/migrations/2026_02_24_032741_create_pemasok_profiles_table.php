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
            $table->string('nama_perusahaan');
            $table->string('kategori_barang'); // cth: Sembako, Daging, Sayur
            $table->string('nama_pic');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('info_bank')->nullable(); // cth: BCA 123456 a.n PT Pangan
            $table->enum('status_kemitraan', ['aktif', 'nonaktif'])->default('aktif');
            $table->decimal('tagihan_berjalan', 15, 2)->default(0); // Hutang LKBB ke pemasok
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemasok_profiles');
    }
};