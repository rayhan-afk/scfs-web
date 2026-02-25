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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('order_id')->nullable()->unique();
            $table->decimal('total_amount', 15, 2); // Sesuai dashboard
            
            // Status diubah ke string agar bisa nampung 'lunas', 'pending', 'failed'
            $table->string('status')->default('pending'); 
            
            // Type diubah ke string agar bisa nampung 'donation', 'loan', 'purchase'
            $table->string('type')->default('purchase'); 
            
            $table->text('description')->nullable();
            $table->json('meta_data')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};