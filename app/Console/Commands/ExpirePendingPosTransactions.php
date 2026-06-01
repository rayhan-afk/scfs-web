<?php

namespace App\Console\Commands;

use App\Models\MerchantProduct;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePendingPosTransactions extends Command
{
    protected $signature = 'pos:expire-pending {--minutes=15 : Batas kadaluwarsa transaksi QR pending (menit)}';

    protected $description = 'Expire transaksi POS QR yang pending lebih dari N menit dan kembalikan stok kantin.';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $threshold = now()->subMinutes($minutes);

        $expired = Transaction::where('type', 'pembayaran_makanan')
            ->where('status', 'pending')
            ->where('created_at', '<', $threshold)
            ->orderBy('id')
            ->pluck('id');

        if ($expired->isEmpty()) {
            $this->info('Tidak ada transaksi QR pending yang kadaluwarsa.');
            return self::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($expired as $id) {
            try {
                DB::transaction(function () use ($id, &$processed, &$skipped) {
                    $trx = Transaction::where('id', $id)
                            ->lockForUpdate()
                            ->first();

                    if (!$trx || $trx->status !== 'pending') {
                        $skipped++;
                        return;
                    }

                    $snapshot = $trx->cart_snapshot ?? [];
                    foreach ($snapshot as $item) {
                        if (empty($item['product_id']) || empty($item['qty'])) continue;
                        MerchantProduct::where('id', $item['product_id'])
                            ->increment('stok', (int) $item['qty']);
                    }

                    $trx->update([
                        'status'      => 'expired',
                        'description' => trim(($trx->description ?? '') . ' [AUTO-EXPIRED]'),
                    ]);

                    $processed++;
                });
            } catch (\Throwable $e) {
                $this->error("Gagal expire transaksi {$id}: " . $e->getMessage());
                report($e);
            }
        }

        $this->info("Selesai. Expired: {$processed}, Lewati: {$skipped}, Batas: {$minutes} menit.");
        return self::SUCCESS;
    }
}
