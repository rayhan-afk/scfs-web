<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Menambahkan ID Penerima Dana (Merchant)
            $table->foreignId('merchant_id')->nullable()->after('user_id')
                  ->constrained('users')->nullOnDelete();
            
            // Menambahkan nominal potongan LKBB untuk audit
            $table->decimal('fee_lkbb', 15, 2)->default(0)->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn(['merchant_id', 'fee_lkbb']);
        });
    }
};