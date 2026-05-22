<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanReturn extends Model
{
    protected $fillable = [
        'supply_order_id',
        'merchant_id',
        'supplier_id',
        'alasan',
        'deskripsi_masalah',
        'foto_bukti',
        'solusi_diajukan',
        'status',
        'catatan_pemasok',
        'catatan_lkbb',
    ];

    // Relasi ke Order Utama
    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'supply_order_id');
    }

    // Relasi ke Merchant (User)
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    // Relasi ke Pemasok/Supplier (User)
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }
}