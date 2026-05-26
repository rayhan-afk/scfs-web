<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',              // Pastikan ini ada
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Relasi: User punya banyak Produk (Khusus Merchant/Pemasok)
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Relasi: User punya banyak Transaksi
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    // Tambahkan ini di dalam class User
    public function merchantProfile()
    {
        return $this->hasOne(\App\Models\MerchantProfile::class);
    }
    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }
    // Relasi ke profil mahasiswa
    public function mahasiswaProfile()
    {
        return $this->hasOne(MahasiswaProfile::class);
    }
    public function pemasokProfile()
    {
        return $this->hasOne(PemasokProfile::class);
    }
    public function investorProfile()
    {
        return $this->hasOne(InvestorProfile::class);
    }
    public function donaturProfile()
    {
        return $this->hasOne(DonaturProfile::class);
    }   
    public function latestLogin()
    {
        return $this->hasOne(LoginLog::class)->latestOfMany('login_at');
    }
    public function merchantProducts() {
    return $this->hasMany(MerchantProduct::class, 'merchant_id');
}

    // ============================================================
    //  Buku Besar Entitas — relasi agregat per entitas
    // ============================================================

    /** PO dimana user ini bertindak sebagai pemasok (penerima dana talangan LKBB). */
    public function supplyOrdersAsPemasok()
    {
        return $this->hasMany(\App\Models\SupplyOrder::class, 'pemasok_id');
    }

    /** PO dimana user ini bertindak sebagai merchant (pemohon talangan). */
    public function supplyOrdersAsMerchant()
    {
        return $this->hasMany(\App\Models\SupplyOrder::class, 'merchant_id');
    }

    /**
     * Riwayat pencairan dana milik user ini.
     * Catatan: kolom `merchant_id` di tabel withdrawals adalah FK user yang generik
     * (dipakai untuk merchant DAN pemasok), bukan flag role.
     */
    public function withdrawals()
    {
        return $this->hasMany(\App\Models\Withdrawal::class, 'merchant_id');
    }

    /** Riwayat setoran tunai (hanya merchant yang melakukan setoran ke LKBB). */
    public function setoranTunais()
    {
        return $this->hasMany(\App\Models\SetoranTunai::class, 'merchant_id');
    }

    /** Transaksi dimana user ini menjadi merchant penjual (POS kantin). */
    public function transactionsAsMerchant()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'merchant_id');
    }

    /** Produk yang disuplai pemasok ini. */
    public function produkPemasoks()
    {
        return $this->hasMany(\App\Models\ProdukPemasok::class, 'user_id');
    }
}
