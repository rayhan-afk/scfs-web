<?php

namespace App\Services\Finance;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class FinanceService
{
    /**
     * Transfer saldo antar Wallet (Double Entry).
     * Digunakan untuk: Pembayaran Kantin, Profit Sharing, Bayar Supplier.
     */
    public function transfer(Wallet $from, Wallet $to, float $amount, string $type, string $description, array $meta = []): Transaction
    {
        // 1. Validasi Awal
        if ($amount <= 0) {
            throw new Exception("Nominal transaksi harus lebih dari 0.");
        }
        if ($from->id === $to->id) {
            throw new Exception("Tidak bisa transfer ke dompet sendiri.");
        }

        // 2. Mulai Database Transaction (ACID)
        // Kalau ada error di baris manapun dalam blok ini, semua perubahan dibatalkan (Rollback).
        return DB::transaction(function () use ($from, $to, $amount, $type, $description, $meta) {
            
            // 3. PESSIMISTIC LOCKING (Anti-Race Condition)
            // Kita "kunci" data pengirim & penerima di database.
            // User lain harus antri sampai proses ini selesai.
            $sender = Wallet::where('id', $from->id)->lockForUpdate()->first();
            $receiver = Wallet::where('id', $to->id)->lockForUpdate()->first();

            // Cek Saldo (harus dicek SETELAH dikunci agar akurat)
            if ($sender->balance < $amount) {
                throw new Exception("Saldo tidak mencukupi. Sisa: Rp " . number_format($sender->balance));
            }

            // 4. Buat Bukti Transaksi (Header)
            $trx = Transaction::create([
                'user_id'       => $sender->user_id, // Inisiator transaksi
                'order_id'      => 'TRX-' . strtoupper(Str::random(10)), // Format order_id kamu
                'total_amount'  => $amount,
                'status'        => 'success', // Langsung sukses karena internal transfer
                'type'          => $type,
                'description'   => $description,
                'meta_data'     => $meta,
            ]);

            // 5. Eksekusi Perpindahan Uang (Mutasi Saldo)

            // A. PENGIRIM (KREDIT / BERKURANG)
            $sender->balance -= $amount;
            $sender->save();

            LedgerEntry::create([
                'transaction_id' => $trx->id,
                'wallet_id'      => $sender->id,
                'entry_type'     => 'CREDIT', // Uang Keluar
                'amount'         => $amount,
                'balance_after'  => $sender->balance // Audit trail saldo akhir
            ]);

            // B. PENERIMA (DEBIT / BERTAMBAH)
            $receiver->balance += $amount;
            $receiver->save();

            LedgerEntry::create([
                'transaction_id' => $trx->id,
                'wallet_id'      => $receiver->id,
                'entry_type'     => 'DEBIT', // Uang Masuk
                'amount'         => $amount,
                'balance_after'  => $receiver->balance
            ]);

            return $trx;
        });
    }

    /**
     * Top Up / Deposit (Uang Masuk dari Luar).
     * Digunakan untuk: Suntikan Modal Investor, Donasi Masuk, Topup Saldo Mhs via Bank.
     * Hanya ada 1 Ledger Entry (DEBIT).
     */
    public function deposit(Wallet $wallet, float $amount, string $source, string $desc): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $source, $desc) {
            // Lock wallet penerima
            $receiver = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $trx = Transaction::create([
                'user_id'       => $receiver->user_id,
                'order_id'      => 'DEP-' . strtoupper(Str::random(10)),
                'total_amount'  => $amount,
                'status'        => 'success',
                'type'          => 'TOPUP',
                'description'   => $desc,
                'meta_data'     => ['source' => $source],
            ]);

            // Tambah Saldo
            $receiver->balance += $amount;
            $receiver->save();

            // Catat Ledger (Debit)
            LedgerEntry::create([
                'transaction_id' => $trx->id,
                'wallet_id'      => $receiver->id,
                'entry_type'     => 'DEBIT',
                'amount'         => $amount,
                'balance_after'  => $receiver->balance,
            ]);

            return $trx;
        });
    }
}