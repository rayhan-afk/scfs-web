<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineOrder extends Model
{
    protected $fillable = [
        'order_id', 'mahasiswa_id', 'merchant_id', 
        'total_amount', 'catatan_pembeli', 'status'
    ];

    public function items() {
        return $this->hasMany(OnlineOrderItem::class);
    }
    
    public function mahasiswa() {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }
}