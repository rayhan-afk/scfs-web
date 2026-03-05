<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyOrderDetail extends Model
{
    //
    protected $fillable = [
        'supply_order_id', 'bahan_baku_id', 
        'jumlah', 'harga_satuan', 'subtotal'
    ];
}
