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
        // 1. BUAT AKUN USER (7 ROLE UTAMA)
        // ==========================================
        
        // 1. Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com', // 👈 Email Admin
            'password' => Hash::make('password'),
            'role' => 'admin',
            'identity_code' => 'ADM-001',
            'phone_number' => '081200000001',
        ]);

        // 2. LKBB (Keuangan)
        User::create([
            'name' => 'Petugas Keuangan',
            'email' => 'finance@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'lkbb',
            'identity_code' => 'LKB-001',
            'phone_number' => '081200000002',
        ]);

        // 3. Merchant (Kantin)
        $merchant = User::create([
            'name' => 'Kantin Berkah',
            'email' => 'kantin@gmail.com', // 👈 Email Penjual
            'password' => Hash::make('password'),
            'role' => 'merchant',
            'identity_code' => 'MER-001',
            'phone_number' => '081200000003',
        ]);

        // 4. Pemasok (Supplier)
        $pemasok = User::create([
            'name' => 'CV. Sumber Rejeki',
            'email' => 'pemasok@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'pemasok',
            'identity_code' => 'SUP-001',
            'phone_number' => '081200000004',
        ]);

        // 5. Mahasiswa (Penerima Beasiswa)
        $mahasiswa = User::create([
            'name' => 'Budi Santoso',
            'email' => 'mhs@gmail.com', // 👈 Email Pembeli (Mahasiswa)
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
            'identity_code' => '13521099',
            'phone_number' => '081200000005',
        ]);

        // 6. Investor
        User::create([
            'name' => 'Pak Hartono',
            'email' => 'investor@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'investor',
            'identity_code' => 'INV-001',
            'phone_number' => '081200000006',
        ]);

        // 7. Donatur
        User::create([
            'name' => 'Ibu Sri Mulyani',
            'email' => 'donatur@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'donatur',
            'identity_code' => 'DON-001',
            'phone_number' => '081200000007',
        ]);

        // ==========================================
        // 2. BUAT DOMPET (WALLET)
        // ==========================================

        // Buat Dompet untuk Mahasiswa (Saldo Bantuan Rp 1.500.000)
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

        // Produk Merchant (Makanan & Minuman)
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
            'user_id' => $merchant->id,
            'name' => 'Kerupuk Kaleng',
            'price' => 1000,
            'stock' => 200,
            'category' => 'snack',
            'is_active' => true,
        ]);

        // Produk Pemasok (Bahan Baku)
        Product::create([
            'user_id' => $pemasok->id,
            'name' => 'Beras Premium 50kg',
            'price' => 600000,
            'stock' => 10,
            'category' => 'bahan_baku',
            'is_active' => true,
        ]);
    }
}