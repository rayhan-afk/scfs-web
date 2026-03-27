<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantProduct extends Model
{
   protected $fillable = [
        'merchant_id', 'nama_produk', 'foto_produk', 
        'kategori', 'harga_pokok', 'harga_jual', 'is_tersedia'
    ];

    public function merchant() {
        return $this->belongsTo(User::class, 'merchant_id');
    }
    public function priceHistories() {
    return $this->hasMany(ProductPriceHistory::class)->latest();
}
}