<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiPemasok extends Model
{
    use HasFactory;

    // Mengizinkan semua kolom diisi secara massal kecuali ID
    protected $guarded = ['id'];

    // Memastikan waktu_produksi dibaca sebagai format tanggal/waktu oleh Laravel
    protected $casts = [
        'waktu_produksi' => 'datetime',
    ];

    // Relasi ke tabel user (pemasok)
    public function pemasok()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}