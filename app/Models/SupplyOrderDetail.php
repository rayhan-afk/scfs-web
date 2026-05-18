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
   // Relasi balik ke induk PO
    public function supplyOrder()
    {
        return $this->belongsTo(SupplyOrder::class, 'supply_order_id');
    }

    // Relasi ke Produk/Barang Pemasok
    public function produkPemasok()
    {
        return $this->belongsTo(ProdukPemasok::class, 'produk_pemasok_id');
    }
}