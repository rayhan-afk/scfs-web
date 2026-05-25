<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->string('nama_kurir')->nullable()->after('kurir');
            $table->string('no_hp_kurir', 15)->nullable()->after('nama_kurir');
        });
    }

    public function down(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->dropColumn(['nama_kurir', 'no_hp_kurir']);
        });
    }
};
