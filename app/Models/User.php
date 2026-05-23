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
}
