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

    // Otomatis ubah JSON info_rekening menjadi Array PHP
    protected $casts = [
        'info_rekening' => 'array',
    ];

    // Accessor: Mengubungkan $supplier->nama_bank ke data di dalam JSON info_rekening
    public function getNamaBankAttribute()
    {
        return $this->info_rekening['nama_bank'] ?? $this->info_rekening['bank'] ?? 'Bank';
    }

    // Accessor: Menghubungkan $supplier->nomor_rekening ke data di dalam JSON info_rekening
    public function getNomorRekeningAttribute()
    {
        return $this->info_rekening['nomor_rekening'] ?? $this->info_rekening['no_rekening'] ?? '-';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}