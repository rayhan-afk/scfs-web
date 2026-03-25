<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 
        'status_verifikasi', 
        'nama_usaha', 
        'nama_pemilik', 
        'nik', 
        'no_hp', 
        'alamat_gudang', 
        'info_rekening', 
        'foto_ktp',
        'foto_usaha',
        'catatan_penolakan'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}