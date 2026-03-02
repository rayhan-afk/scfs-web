<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',             // <-- BARU: Penanda ini dompet siapa (Mhs/Kantin/LKBB)
        'account_number',
        'pin',
        'balance',          // <-- SUDAH DIGANTI DARI grant_balance
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Buku Besar.
     * Mengambil semua riwayat mutasi (masuk/keluar) dompet ini.
     */
    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class)->latest();
    }
    
    // Helper Methods (Biar kodingan nanti lebih enak dibaca)
    public function isLkbb() { return $this->type === 'LKBB_MASTER'; }
    public function isMerchant() { return $this->type === 'MERCHANT'; }
}