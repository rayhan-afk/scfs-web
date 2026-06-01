<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            // REALIZE_TUNAI (setoran fisik dari Petugas LKBB) tidak terikat ke satu Transaction.
            // Satu setoran fisik bisa menutup banyak Transaction tunai sekaligus.
            $table->dropForeign(['transaction_id']);
            $table->foreignId('transaction_id')->nullable()->change();
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->foreignId('transaction_id')->nullable(false)->change();
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
        });
    }
};
