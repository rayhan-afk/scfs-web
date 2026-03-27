<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanBaku extends Model
{
    //
    protected $fillable = [
        'nama_bahan', 'kategori', 'satuan', 
        'harga_estimasi', 'foto_bahan', 'is_tersedia'
    ];
}
