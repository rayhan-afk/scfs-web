<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'total_amount',
        'status',
        'type',
        'description', // <-- BARU: Catatan transaksi
        'meta_data',   // <-- BARU: Data detail json
    ];

    // Otomatis convert JSON di database jadi Array di PHP
    protected $casts = [
        'meta_data' => 'array', 
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke detail mutasi.
     * Satu transaksi punya banyak entry (minimal 2: Debit & Kredit).
     */
    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}