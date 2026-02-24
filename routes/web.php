<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // 1. TRAFFIC CONTROLLER (Polisi Lalu Lintas)
    // Tugasnya cuma mengarahkan setelah login
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


    // 2. ROUTE ADMIN (Kita bersihkan middleware-nya biar ga error)
    // Pengamannya kita taruh di file blade-nya saja (Lihat langkah 2)
    Volt::route('/admin/dashboard', 'dashboard.admin') 
        ->name('admin.dashboard');


    // 3. ROUTE LKBB
    Volt::route('/lkbb/dashboard', 'dashboard.lkbb')
        ->name('lkbb.dashboard');

    // ==========================
    // ROUTE LKBB
    // ==========================
    Volt::route('/lkbb/dashboard', 'dashboard.lkbb')
        ->name('lkbb.dashboard');


    // 🔥 PINDAHKAN KE SINI (di luar closure)
    Volt::route('/lkbb/wallets', 'lkbb.wallet-index')
        ->name('lkbb.wallets');

    Route::view('lkbb/products', 'livewire.lkbb.product-index')
        ->name('products.index');

    // Perhatikan ada tambahan prefix 'lkbb.'
Volt::route('/users', 'lkbb.user-management')->name('users.index');

Volt::route('/supply-chain/create', 'lkbb.supply-chain.create')->name('supply-chain.create');
Volt::route('/supply-chain/approval', 'lkbb.supply-chain.approval')->name('supply-chain.approval'); 
Volt::route('/supply-chain/bills', 'lkbb.supply-chain.bills')->name('supply-chain.bills');
Volt::route('/approval/merchant', 'lkbb.approval.merchant')->name('approval.merchant');
Volt::route('/approval/mahasiswa', 'lkbb.approval.mahasiswa')->name('approval.mahasiswa');
Volt::route('/keuangan/merchant', 'lkbb.keuangan.merchant')->name('keuangan.merchant');
Volt::route('/keuangan/pemasok', 'lkbb.keuangan.pemasok')->name('keuangan.pemasok');
Volt::route('/keuangan/mahasiswa', 'lkbb.keuangan.mahasiswa')->name('saldo.bantuan');
Volt::route('/keuangan/pencairan', 'lkbb.keuangan.pencairan')->name('keuangan.pencairan');
Volt::route('/keuangan/penagihan', 'lkbb.keuangan.penagihan')->name('keuangan.penagihan');
// 4. PROFILE
    Route::view('profile', 'profile')
        ->name('profile');
});

require __DIR__.'/auth.php';