<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineOrderItem extends Model
{
    protected $fillable = [
        'online_order_id', 'merchant_product_id', 
        'nama_produk_snapshot', 'harga_pokok_snapshot', 
        'harga_jual_snapshot', 'qty', 'subtotal'
    ];

    public function order() {
        return $this->belongsTo(OnlineOrder::class, 'online_order_id');
    }
}