<?php

namespace App\Models; // <-- Ini wajib ada dan huruf 'A' serta 'M' harus besar

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model // <-- Nama class wajib sama dengan nama file
{
    use HasFactory;
    
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}