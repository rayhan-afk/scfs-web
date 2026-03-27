<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceHistory extends Model
{
    //
    protected $fillable = ['merchant_product_id', 'harga_pokok_lama', 'harga_pokok_baru', 'harga_jual_lama', 'harga_jual_baru'];
}
