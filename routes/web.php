<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;

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
        } else {
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
    });

require __DIR__.'/auth.php';