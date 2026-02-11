<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. BUAT AKUN USER
        // ==========================================
        
        // 1. Admin (Email diubah jadi admin@admin)
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin', // 👈 INI SUDAH DIGANTI
            'password' => Hash::make('password'),
            'role' => 'admin',
            'identity_code' => 'ADM-001',
            'phone_number' => '081211111111',
        ]);

        // 2. LKBB (Keuangan)
        User::create([
            'name' => 'Petugas Keuangan',
            'email' => 'finance@scfs.com',
            'password' => Hash::make('password'),
            'role' => 'lkbb',
            'identity_code' => 'LKB-001',
        ]);

        // 3. Merchant (Kantin)
        $merchant = User::create([
            'name' => 'Kantin Bu Bariah',
            'email' => 'kantin@scfs.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
            'identity_code' => 'MER-001',
            'phone_number' => '081233333333',
        ]);

        // 4. Pemasok (Supplier)
        $pemasok = User::create([
            'name' => 'CV. Sumber Rejeki',
            'email' => 'pemasok@scfs.com',
            'password' => Hash::make('password'),
            'role' => 'pemasok',
            'identity_code' => 'SUP-001',
        ]);

        // 5. Mahasiswa (Penerima Beasiswa)
        $mahasiswa = User::create([
            'name' => 'Budi Santoso',
            'email' => 'mhs@scfs.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
            'identity_code' => '13521099',
            'phone_number' => '081255555555',
        ]);

        // 6. Investor
        User::create([
            'name' => 'Pak Hartono',
            'email' => 'investor@scfs.com',
            'password' => Hash::make('password'),
            'role' => 'investor',
            'identity_code' => 'INV-001',
        ]);

        // 7. Donatur
        User::create([
            'name' => 'Ibu Sri Mulyani',
            'email' => 'donatur@scfs.com',
            'password' => Hash::make('password'),
            'role' => 'donatur',
            'identity_code' => 'DON-001',
        ]);

        // ==========================================
        // 2. BUAT DOMPET (WALLET)
        // ==========================================

        Wallet::create([
            'user_id' => $mahasiswa->id,
            'account_number' => 'W-MHS-' . time(),
            'pin' => '123456',
            'grant_balance' => 1500000, 
            'is_active' => true,
        ]);

        // ==========================================
        // 3. BUAT PRODUK (DAGANGAN)
        // ==========================================

        Product::create([
            'user_id' => $merchant->id,
            'name' => 'Nasi Ayam Penyet',
            'price' => 15000,
            'stock' => 50,
            'category' => 'makanan_berat',
            'is_active' => true,
        ]);

        Product::create([
            'user_id' => $merchant->id,
            'name' => 'Es Teh Manis',
            'price' => 3000,
            'stock' => 100,
            'category' => 'minuman',
            'is_active' => true,
        ]);

        Product::create([
            'user_id' => $pemasok->id,
            'name' => 'Beras 50kg',
            'price' => 600000,
            'stock' => 10,
            'category' => 'bahan_baku',
            'is_active' => true,
        ]);
    }
}