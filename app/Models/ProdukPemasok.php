<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdukPemasok extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produk_pemasoks'; 

    protected $fillable = [
        'user_id',
        'sku',
        'nama_produk',
        'deskripsi',
        'harga_modal',      // <-- PENGGANTI GROSIR
        'margin_pemasok',   // <-- PENGGANTI GROSIR
        'stok_sekarang',
        'batas_minimum_stok',
        'foto_produk',
        'status',
        'satuan',
    ];

    // Relasi: Produk ini milik seorang Pemasok (User)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Satu produk bisa punya banyak riwayat opname
    public function riwayatOpnames()
    {
        return $this->hasMany(RiwayatOpnamePemasok::class, 'produk_pemasok_id');
    }
}