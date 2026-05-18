<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('supply_order_details', function (Blueprint $table) {
            $table->boolean('is_added_to_pos')->default(false)->after('subtotal');
        });
    }
    public function down(): void {
        Schema::table('supply_order_details', function (Blueprint $table) {
            $table->dropColumn('is_added_to_pos');
        });
    }
};