<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MahasiswaProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id']; // Semua kolom boleh diisi kecuali ID

    // Relasi balik ke tabel User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke tabel Pengajuan Bantuan (Anak)
    public function pengajuans()
    {
        return $this->hasMany(PengajuanBantuan::class);
    }
}