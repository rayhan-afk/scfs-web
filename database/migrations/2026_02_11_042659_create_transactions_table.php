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
        
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        
        // Order hanya untuk transaksi purchase
        $table->string('order_id')->nullable()->unique();
        
        $table->decimal('total_amount', 15, 2);
        
        $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])
              ->default('pending');
        
        // purchase, topup, donation, supplier_payment, dll
        $table->string('type')->default('purchase');
        
        // Tambahan untuk fleksibilitas
        $table->text('description')->nullable();
        $table->json('meta_data')->nullable();
        
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
