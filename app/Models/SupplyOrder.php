<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupplyOrderDetail; // Pastikan ini ada

class SupplyOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id']; // atau $fillable = [...]

    // --- TAMBAHKAN KODE INI ---
    public function details()
    {
        return $this->hasMany(SupplyOrderDetail::class, 'supply_order_id');
    }
    // --------------------------
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}