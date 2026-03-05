<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    // Menentukan tabel secara eksplisit (opsional, tapi good practice)
    protected $table = 'withdrawals';

    // Mencegah Mass Assignment Vulnerability
    protected $fillable = [
        'nomor_pencairan',
        'merchant_id',
        'nominal_kotor',
        'potongan_lkbb',
        'nominal_bersih',
        'info_pencairan',
        'status',
        'catatan_lkbb',
    ];

    /**
     * Relasi ke entitas User (Pemilik Akun)
     * Menggunakan 'merchant_id' sebagai foreign key ke tabel 'users'
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Relasi ke entitas Profil Kantin
     * Ini SANGAT PENTING untuk halaman Admin LKBB nanti agar kita bisa 
     * menampilkan "Nama Kantin" dari data penarikan ini.
     * * Foreign key di tabel merchant_profiles adalah 'user_id', 
     * sedangkan di tabel withdrawals adalah 'merchant_id'.
     */
    public function merchantProfile()
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_id', 'user_id');
    }
}