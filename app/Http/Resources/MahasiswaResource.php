<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MahasiswaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * $this di sini mewakili data User yang dikirim dari Controller.
     */
    public function toArray($request)
    {
        // Tarik data profil mahasiswa
        $profile = \App\Models\MahasiswaProfile::where('user_id', $this->id)->first();

        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
            
            // Paketkan semua data mahasiswa ke dalam key 'student_profile'
            'student_profile' => $profile ? [
                'nim'               => $profile->nim,
                'jurusan'           => $profile->jurusan,
                'no_hp'             => $profile->no_hp,
                'alamat'            => $profile->alamat,
                'semester'          => $profile->semester,
                'ipk'               => (float) $profile->ipk,
                'status_verifikasi' => $profile->status_verifikasi,
                'status_bantuan'    => $profile->status_bantuan,
                'saldo'             => (int) $profile->saldo, 
                
                // 👇 INI DIA TAMBAHANNYA: Mengirimkan URL lengkap foto profil 👇
                'ktm_image'         => $profile->ktm_image ? asset('storage/' . $profile->ktm_image) : null,
            ] : null
        ];
    }
}