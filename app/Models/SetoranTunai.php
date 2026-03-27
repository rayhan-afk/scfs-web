<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SetoranTunai extends Model {
    protected $fillable = ['nomor_setoran', 'merchant_id', 'nominal', 'status', 'nama_petugas'];
    
    public function merchant() {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}