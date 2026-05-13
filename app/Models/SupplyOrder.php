<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupplyOrderDetail;

class SupplyOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id']; 

    // Relasi ke Detail Pesanan
    public function details()
    {
        return $this->hasMany(SupplyOrderDetail::class, 'supply_order_id');
    }

    // Relasi ke Merchant Pemesan
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
    
    // --- TAMBAHAN BARU: Relasi ke Pemasok ---
    public function pemasok()
    {
        return $this->belongsTo(User::class, 'pemasok_id');
    }
}