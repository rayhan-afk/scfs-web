<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hapus dompet LKBB skema lama (Skema B).
     *
     * LKBB_MASTER / DONATION_POOL / LKBB_PROFIT digantikan permanen oleh
     * LKBB_INVESTMENT / LKBB_DONATION / LKBB_OPERATIONAL (Skema A).
     *
     * FK cascade menangani sisa data otomatis:
     *  - ledger_entries.wallet_id            -> cascadeOnDelete (baris ikut terhapus)
     *  - transactions.sender_wallet_id       -> nullOnDelete
     *  - transactions.receiver_wallet_id     -> nullOnDelete
     */
    public function up(): void
    {
        DB::table('wallets')
            ->whereIn('type', ['LKBB_MASTER', 'DONATION_POOL', 'LKBB_PROFIT'])
            ->delete();
    }

    /**
     * Tidak bisa di-rollback: data dompet skema lama dihapus permanen.
     */
    public function down(): void
    {
        // no-op
    }
};
