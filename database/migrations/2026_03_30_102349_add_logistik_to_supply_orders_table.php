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
        Schema::table('supply_orders', function (Blueprint $table) {
            // Menambahkan kolom logistik setelah kolom status
            $table->string('kurir')->nullable()->after('status');
            $table->string('no_resi')->nullable()->after('kurir');
            $table->json('tracking_history')->nullable()->after('no_resi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->dropColumn(['kurir', 'no_resi', 'tracking_history']);
        });
    }
};