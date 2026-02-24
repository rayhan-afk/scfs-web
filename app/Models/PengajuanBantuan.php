<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanBantuan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi balik ke Profil Mahasiswa
    public function mahasiswaProfile()
    {
        return $this->belongsTo(MahasiswaProfile::class);
    }
}