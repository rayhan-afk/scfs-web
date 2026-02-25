<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('account_number')->unique(); 
            $table->string('pin')->nullable(); 
            
            // Ubah grant_balance jadi balance sesuai kodingan dashboard LKBB
            $table->decimal('balance', 15, 2)->default(0); 
            
            // Masukkan tipe dompet langsung di sini
            $table->string('type')->default('REGULAR'); // cth: LKBB_MASTER, DONATION_POOL
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};