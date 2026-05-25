<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: MODIFY ENUM is not supported on SQLite (used in testing)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE supply_orders
                MODIFY status ENUM(
                    'menunggu_pemasok',
                    'menunggu_lkbb',
                    'diproses_pemasok',
                    'dikirim',
                    'selesai',
                    'ditolak'
                ) NOT NULL DEFAULT 'menunggu_pemasok'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE supply_orders
                MODIFY status ENUM(
                    'menunggu_lkbb',
                    'diproses_pemasok',
                    'dikirim',
                    'selesai',
                    'ditolak'
                ) NOT NULL DEFAULT 'menunggu_lkbb'
            ");
        }
    }
};