<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemasokProfile extends Model
{
    // Beri tahu Laravel nama tabel pastinya
    protected $table = 'pemasok_profiles';

    // Proteksi Mass Assignment
    protected $fillable = [
        'user_id',
        'nama_perusahaan',
        'kategori_barang',
        'nama_pic',
        'no_hp',
        'alamat',
        'info_bank',
        'status_kemitraan',
        'tagihan_berjalan'
    ];

    // Relasi balik ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function supplyChains()
    {
        // Mengacu ke supplier_id di tabel supply_chains
        return $this->hasMany(SupplyChain::class, 'supplier_id', 'user_id');
    }
    public function riwayatPesanan()
    {
        return $this->hasMany(SupplyChain::class, 'supplier_id', 'user_id');
    }
}
