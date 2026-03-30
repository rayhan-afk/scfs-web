<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatOpnamePemasok extends Model
{
    use HasFactory;

    protected $table = 'riwayat_opname_pemasoks';

    protected $fillable = [
        'produk_pemasok_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'keterangan'
    ];

    // Relasi balik ke produk
    public function produk()
    {
        return $this->belongsTo(ProdukPemasok::class, 'produk_pemasok_id');
    }
}