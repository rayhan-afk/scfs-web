<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MerchantProfile;
use Illuminate\Support\Facades\Hash;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Merchant dengan status PENDING (Untuk ditest Approve/Reject)
        $user1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi.kantin@example.com',
            'password' => Hash::make('password123'),
        ]);

        MerchantProfile::create([
            'user_id' => $user1->id,
            'nama_kantin' => 'Kantin Mang Budi',
            'nama_pemilik' => 'Budi Santoso',
            'lokasi_blok' => 'Kantin Barat Blok A',
            'persentase_bagi_hasil' => 10,
            'status_toko' => 'tutup',
            'status_verifikasi' => 'pending',
        ]);

        // 2. Buat Merchant dengan status DISETUJUI
        $user2 = User::create([
            'name' => 'Siti Aminah',
            'email' => 'siti.kopi@example.com',
            'password' => Hash::make('password123'),
        ]);

        MerchantProfile::create([
            'user_id' => $user2->id,
            'nama_kantin' => 'Kedai Kopi Bu Siti',
            'nama_pemilik' => 'Siti Aminah',
            'lokasi_blok' => 'Kantin Timur Blok C',
            'persentase_bagi_hasil' => 10,
            'status_toko' => 'buka',
            'status_verifikasi' => 'disetujui',
        ]);

        // 3. Buat Merchant dengan status DITOLAK
        $user3 = User::create([
            'name' => 'Asep Hidayat',
            'email' => 'asep.gorengan@example.com',
            'password' => Hash::make('password123'),
        ]);

        MerchantProfile::create([
            'user_id' => $user3->id,
            'nama_kantin' => 'Gorengan Kang Asep',
            'nama_pemilik' => 'Asep Hidayat',
            'lokasi_blok' => 'Area Parkir Utama',
            'persentase_bagi_hasil' => 10,
            'status_toko' => 'tutup',
            'status_verifikasi' => 'ditolak',
        ]);
    }
}