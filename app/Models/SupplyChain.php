<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplyChain extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
        'capital_amount' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relasi ke Toko/Klien yang memesan barang
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    // Relasi ke Pembuat/Vendor yang membuat barang
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    // Generate otomatis nomor invoice setiap ada pengajuan baru
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->invoice_number)) {
                $model->invoice_number = 'INV-SC-' . date('Ymd') . '-' . strtoupper(uniqid());
            }
        });
    }
}