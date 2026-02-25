<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. UPGRADE TABEL WALLETS
        Schema::table('wallets', function (Blueprint $table) {
            // A. Generalisasi Saldo
            // Kita ubah nama 'grant_balance' jadi 'balance' agar lebih umum
            // Cek dulu apakah kolomnya ada sebelum rename untuk menghindari error
            if (Schema::hasColumn('wallets', 'grant_balance')) {
                $table->renameColumn('grant_balance', 'balance');
            }

            // B. Identitas Wallet
            // Tambah tipe wallet agar sistem tahu ini dompet siapa
            // TYPE: 'STUDENT' (default), 'MERCHANT', 'SUPPLIER', 'LKBB_MASTER', 'LKBB_DONATION'
            if (!Schema::hasColumn('wallets', 'type')) {
                $table->string('type')->default('STUDENT')->after('user_id')->index();
            }

            // Opsional: Mencegah 1 user punya 2 wallet dengan tipe yang sama
            // $table->unique(['user_id', 'type']); 
        });

        // 2. UPGRADE TABEL TRANSACTIONS
        Schema::table('transactions', function (Blueprint $table) {
            // A. Konteks Transaksi
            // Tambah kolom deskripsi untuk catatan (misal: "Bayar Sayur Asem")
            if (!Schema::hasColumn('transactions', 'description')) {
                $table->string('description')->nullable()->after('total_amount');
            }
            
            // B. Fleksibilitas Data (Metadata)
            // PENTING untuk LKBB: Simpan data bank, no referensi eksternal, dll.
            if (!Schema::hasColumn('transactions', 'meta_data')) {
                $table->json('meta_data')->nullable()->after('description');
            }
        });

        // 3. BUAT TABEL BARU: LEDGER ENTRIES (Buku Besar)
        // Ini Jantungnya LKBB. Mencatat Double-Entry (Debit/Kredit).
        if (!Schema::hasTable('ledger_entries')) {
            Schema::create('ledger_entries', function (Blueprint $table) {
                $table->id();
                
                // Relasi ke Header Transaksi
                $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
                
                // Relasi ke Wallet yang terdampak
                $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
                
                // DEBIT (Uang Masuk) / CREDIT (Uang Keluar)
                $table->enum('entry_type', ['DEBIT', 'CREDIT'])->index(); 
                
                // Nominal mutasi
                $table->decimal('amount', 15, 2);
                
                // Snapshot saldo setelah transaksi (untuk audit cepat tanpa hitung ulang dari nol)
                $table->decimal('balance_after', 15, 2); 
                
                $table->timestamps();
                
                // Index untuk mempercepat query history per wallet
                $table->index(['wallet_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Urutan rollback: Hapus anak dulu (ledger), baru edit induknya.
        Schema::dropIfExists('ledger_entries');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['description', 'meta_data']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            // Kembalikan nama kolom saldo bantuan
            if (Schema::hasColumn('wallets', 'balance')) {
                $table->renameColumn('balance', 'grant_balance');
            }
            $table->dropColumn('type');
        });
    }
};