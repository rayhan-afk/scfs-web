<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemasokProfile extends Model
{
    protected $table = 'pemasok_profiles';

    protected $fillable = [
        'user_id',
        'nama_perusahaan',
        'kategori_barang',
        'nama_pic',
        'nik',
        'no_hp',
        'alamat',
        'status_verifikasi',
        'foto_ktp',
        'foto_gudang',
        'catatan_penolakan',
        'nama_bank',
        'no_rekening',
        'atas_nama_rekening',
        'status_kemitraan',
        'status_operasional',
        'saldo_pendapatan',
        'tagihan_berjalan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplyChains()
    {
        return $this->hasMany(SupplyChain::class, 'supplier_id', 'user_id');
    }

    public function riwayatPesanan()
    {
        return $this->hasMany(SupplyChain::class, 'supplier_id', 'user_id');
    }
}
