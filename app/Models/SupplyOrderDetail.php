<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProdukPemasok; // <-- Pastikan baris ini ada

class SupplyOrderDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id']; // atau $fillable = [...]

    // --- TAMBAHKAN KODE INI ---
    /**
     * Relasi ke ProdukPemasok (Setiap detail pesanan ini merujuk ke 1 produk)
     */
   // Hapus fungsi bahanBaku() jika masih ada, ganti jadi ini:
    public function produkPemasok()
    {
        return $this->belongsTo(ProdukPemasok::class, 'produk_pemasok_id');
    }
    
    /**
     * Balikan relasi ke SupplyOrder (opsional tapi bagus untuk kedepannya)
     */
    public function order()
    {
        return $this->belongsTo(SupplyOrder::class, 'supply_order_id');
    }
    // --------------------------
}