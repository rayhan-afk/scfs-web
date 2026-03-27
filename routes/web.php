<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Pemasok\ProfilePemasok;
use App\Livewire\Pemasok\ManajemenProduk;
use App\Livewire\Pemasok\LaporanAnalitik;
use App\Livewire\Pemasok\PesananMasuk;
use App\Livewire\Pemasok\RiwayatProduksi;
use App\Livewire\Pemasok\PengirimanLogistik;
use App\Livewire\Pemasok\PengajuanDanaLkbb;
use App\Livewire\Pemasok\TarikDana;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================================
// SEMUA ROUTE BUTUH LOGIN (middleware auth)
// ============================================================
Route::middleware(['auth'])->group(function () {

    // ----------------------------------------------------------
    // TRAFFIC CONTROLLER — arahkan setelah login
    // ----------------------------------------------------------
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'lkbb') {
            return redirect()->route('lkbb.dashboard');
        } elseif ($user->role === 'merchant') {
            return redirect()->route('merchant.dashboard');
        } elseif ($user->role === 'pemasok') {
            // [PERBAIKAN 1] Tambahkan logika redirect untuk Pemasok
            return redirect()->route('pemasok.dashboard'); 
        } else {
            // (Nanti role mahasiswa, investor bisa ditambahkan di atas ini)
            return redirect()->route('profile'); 
        }
    })->name('dashboard');

    // ----------------------------------------------------------
    // PROFILE
    // ----------------------------------------------------------
    Route::view('/profile', 'profile')->name('profile');

    // ----------------------------------------------------------
    // ADMIN ROUTES
    // ----------------------------------------------------------
    Volt::route('/admin/dashboard', 'dashboard.admin')->name('admin.dashboard');

    Volt::route('/admin/users', 'admin.user-management')->name('admin.users.index');

    // Mahasiswa
    Volt::route('/admin/verifikasi-mahasiswa', 'admin.mahasiswa-verification')->name('admin.verification');
    Volt::route('/admin/data-mahasiswa', 'admin.mahasiswa-data')->name('admin.mahasiswa.index');
    Volt::route('/admin/data-mahasiswa/{id}', 'admin.mahasiswa-detail')->name('admin.mahasiswa.detail');

    // Merchant
    Volt::route('/admin/data-merchant', 'admin.merchant-data')->name('admin.merchant.index');
    Volt::route('/admin/data-merchant/{id}', 'admin.merchant-detail')->name('admin.merchant.detail');

    // Pemasok
    Volt::route('/admin/data-pemasok', 'admin.pemasok-data')->name('admin.pemasok.index');
    Volt::route('/admin/data-pemasok/{id}', 'admin.pemasok-detail')->name('admin.pemasok.detail');

    // Investor
    Volt::route('/admin/data-investor', 'admin.investor-data')->name('admin.investor.index');
    Volt::route('/admin/data-investor/{id}', 'admin.investor-detail')->name('admin.investor.detail');

    // Donatur
    Volt::route('/admin/data-donatur', 'admin.donatur-data')->name('admin.donatur.index');
    Volt::route('/admin/data-donatur/{id}', 'admin.donatur-detail')->name('admin.donatur.detail');

    // Monitoring Transaksi
    Volt::route('/admin/monitoring-transaksi', 'admin.monitoring-transaksi')->name('admin.monitoring.index');
    
    // ----------------------------------------------------------
    // LKBB ROUTES
    // ----------------------------------------------------------
    Volt::route('/lkbb/dashboard', 'dashboard.lkbb')->name('lkbb.dashboard');
    Volt::route('/lkbb/wallets', 'lkbb.wallet-index')->name('lkbb.wallets');

    Route::view('/lkbb/products', 'livewire.lkbb.product-index')->name('products.index');

    Volt::route('/users', 'lkbb.user-management')->name('users.index');

    // Supply Chain
    Volt::route('/supply-chain/create', 'lkbb.supply-chain.create')->name('supply-chain.create');
    Volt::route('/supply-chain/approval', 'lkbb.supply-chain.approval')->name('supply-chain.approval');
    Volt::route('/supply-chain/bills', 'lkbb.supply-chain.bills')->name('supply-chain.bills');

    // Approval
    Volt::route('/approval/merchant', 'lkbb.approval.merchant')->name('approval.merchant');
    Volt::route('/approval/mahasiswa', 'lkbb.approval.mahasiswa')->name('approval.mahasiswa');
    Volt::route('/approval/pemasok', 'lkbb.approval.pemasok')->name('approval.pemasok');
    Volt::route('/riwayat/mahasiswa/detail/{id}', 'lkbb.riwayat.detail-mahasiswa')->name('lkbb.riwayat.detail-mahasiswa');

    // Keuangan
    Volt::route('/keuangan/merchant', 'lkbb.keuangan.merchant')->name('keuangan.merchant');
    Volt::route('/keuangan/pemasok', 'lkbb.keuangan.pemasok')->name('keuangan.pemasok');
    Volt::route('/keuangan/mahasiswa', 'lkbb.keuangan.mahasiswa')->name('saldo.bantuan');
    Volt::route('/keuangan/pencairan', 'lkbb.keuangan.pencairan')->name('keuangan.pencairan');
    Volt::route('/keuangan/penagihan', 'lkbb.keuangan.penagihan')->name('keuangan.penagihan');

    // riwat dan detail
    Volt::route('/lkbb/riwayat-approval', 'lkbb.riwayat.riwayat-approval-mahasiswa')->name('lkbb.riwayat');
    Volt::route('/lkbb/riwayat/mahasiswa/{id}', 'lkbb.riwayat.detail-mahasiswa')->name('lkbb.mahasiswa.detail');

    // ----------------------------------------------------------
    // RUTE DASHBOARD MERCHANT
    // ----------------------------------------------------------
    Volt::route('/merchant/dashboard', 'dashboard.merchant')->name('merchant.dashboard');
    
    // ----------------------------------------------------------
    // PEMASOK ROUTES
    // ----------------------------------------------------------
    // [PERBAIKAN 2] Daftarkan rute dashboard pemasok yang mengarah ke file Livewire Volt kita
    Volt::route('/pemasok/dashboard', 'dashboard.pemasok')->name('pemasok.dashboard');

    Route::get('/pemasok/inventaris', ManajemenProduk::class)->name('pemasok.inventaris');
    Route::get('/pemasok/profil', ProfilePemasok::class)->name('pemasok.profil');
   
    Route::get('/pemasok/laporan', \App\Livewire\Pemasok\LaporanAnalitik::class)->name('pemasok.laporan');
   // Di dalam grup middleware pemasok Anda:
    Route::get('/pemasok/riwayat-produksi', RiwayatProduksi::class)->name('pemasok.riwayat-produksi');
    Route::get('/pemasok/pesanan-masuk', PesananMasuk::class)->name('pemasok.pesanan-masuk');
    Route::get('/pemasok/pengajuan-dana-lkbb', PengajuanDanaLkbb::class)->name('pemasok.pengajuan-dana-lkbb');
    Route::get('/pemasok/tarik-dana', TarikDana::class)->name('pemasok.tarik-dana');
    Route::get('/pemasok/pengiriman', PengirimanLogistik::class)->name('pemasok.pengiriman');
});

    Volt::route('/merchant/scan', 'merchant.scan-qr')->name('merchant.scan');
    Volt::route('/merchant/withdraw', 'merchant.withdraw')->name('merchant.withdraw');
    Volt::route('/merchant/katalog', 'merchant.katalog')->name('merchant.katalog');
    Volt::route('/merchant/profile', 'merchant.profile')->name('merchant.profile');
    Volt::route('/merchant/order', 'merchant.order-bahan')->name('merchant.order');
    Volt::route('/merchant/riwayat', 'merchant.riwayat')->name('merchant.riwayat');
    Volt::route('/merchant/penerimaan', 'merchant.penerimaan')->name('merchant.penerimaan');
    Volt::route('/merchant/setoran', 'merchant.setoran')->name('merchant.setoran');


require __DIR__.'/auth.php';