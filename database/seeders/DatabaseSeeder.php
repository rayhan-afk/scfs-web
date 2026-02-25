<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin Utama
        User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Akun Merchant/Kantin
        User::create([
            'name' => 'Kantin Teknik',
            'email' => 'kantin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
        ]);

        User::create([
            'name' => 'LKBB',
            'email' => 'lkbb@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'lkbb',
        ]);

        // 3. Data Mahasiswa + Profilnya
        $jurusan = ['Teknik Informatika', 'Desain Produk', 'Sipil', 'Elektro'];
        $status_verif = ['menunggu', 'disetujui', 'ditolak'];

        for ($i = 1; $i <= 10; $i++) {
            // A. Buat Akun Login dulu di tabel users
            $user = User::create([
                'name' => 'Mahasiswa ' . $i,
                'email' => "mhs$i@gmail.com",
                'password' => Hash::make('password'),
                'role' => 'mahasiswa',
            ]);

            // B. Buat Profil Detailnya di tabel mahasiswa_profiles
            $user->mahasiswaProfile()->create([
                'nim' => '1011' . rand(1000, 9999),
                'jurusan' => $jurusan[array_rand($jurusan)],
                'status_verifikasi' => $status_verif[array_rand($status_verif)],
                'status_bantuan' => 'belum_diajukan',
                'ktm_image' => 'ktm-dummy.jpg',
                'saldo' => 0
            ]);
        }
    }
}