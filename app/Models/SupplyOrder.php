<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyOrder extends Model
{
    //
    protected $fillable = [
        'user_id', 'merchant_id', 'bahan_baku_id', 
        'jumlah', 'total_harga', 'status'
    ];
}
