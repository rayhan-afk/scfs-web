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
use App\Livewire\Pemasok\TarikDana;
use App\Livewire\Lkbb\ApprovalPo; 

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root ke login
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

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
            
            // AMBIL STATUS VERIFIKASI PEMASOK
            $pemasokStatus = $user->pemasokProfile?->status_verifikasi;

            // Kunci akses jika statusnya belum resmi disetujui oleh LKBB
            if ($pemasokStatus !== 'disetujui') {
                return redirect()->route('pemasok.application-status');
            }

            return redirect()->route('pemasok.dashboard'); 
        } else {
            return redirect()->route('profile'); 
        }
    })->name('dashboard');


    // ----------------------------------------------------------
    // PROFILE
    // ----------------------------------------------------------
    Route::view('/profile', 'profile')->name('profile');

    // Notifikasi ROute
    Route::post('/notifications/read/{id}', function ($id) {

    $notification = auth()->user()
        ->notifications()
        ->findOrFail($id);

    $notification->markAsRead();

    return back();

    })->name('notifications.read');

    Route::post('/notifications/read-all', function () {
        auth()->user()
            ->unreadNotifications
            ->markAsRead();
        return back();
    })->name('notifications.readAll');

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
    
    // 🔥 ROUTE BARU: LAPORAN BRANKAS INTI (DETAIL DARI CARD DASHBOARD)
    Volt::route('/lkbb/brankas/investasi', 'lkbb.brankas.investasi')->name('lkbb.brankas.investasi');
    Volt::route('/lkbb/brankas/donasi', 'lkbb.brankas.donasi')->name('lkbb.brankas.donasi');
    Volt::route('/lkbb/brankas/operasional', 'lkbb.brankas.operasional')->name('lkbb.brankas.operasional');
    Volt::route('/lkbb/brankas/perputaran', 'lkbb.brankas.perputaran')->name('lkbb.brankas.perputaran');

    Route::view('/lkbb/products', 'livewire.lkbb.product-index')->name('products.index');
    Volt::route('/users', 'lkbb.user-management')->name('users.index');

    // Token
    Volt::route('/lkbb/injeksi-saldo', 'lkbb.keuangan.injeksi-saldo')->name('lkbb.injeksi-saldo');
    Volt::route('/lkbb/riwayat-injeksi', 'lkbb.keuangan.riwayat-injeksi')->name('lkbb.riwayat-injeksi');

    // Supply Chain
    Route::get('/lkbb/approval-scf', ApprovalPo::class)->name('lkbb.scf.approval');
    Route::view('/lkbb/scf/riwayat', 'livewire.lkbb.riwayat-po')->name('lkbb.scf.riwayat');

    // Approval Master Data
    Volt::route('/approval/merchant', 'lkbb.approval.merchant')->name('approval.merchant');
    Volt::route('/approval/mahasiswa', 'lkbb.approval.mahasiswa')->name('approval.mahasiswa');
    Volt::route('/approval/pemasok', 'lkbb.approval.pemasok')->name('approval.pemasok');
    Volt::route('/riwayat/mahasiswa/detail/{id}', 'lkbb.riwayat.detail-mahasiswa')->name('lkbb.riwayat.detail-mahasiswa');

    // Keuangan LKBB
    Volt::route('/keuangan/merchant', 'lkbb.keuangan.merchant')->name('keuangan.merchant');
    Volt::route('/keuangan/pemasok', 'lkbb.keuangan.pemasok')->name('keuangan.pemasok');
    Volt::route('/keuangan/mahasiswa', 'lkbb.keuangan.mahasiswa')->name('saldo.bantuan');
    Volt::route('/keuangan/pencairan', 'lkbb.keuangan.pencairan')->name('keuangan.pencairan');
    Volt::route('/keuangan/penagihan', 'lkbb.keuangan.penagihan')->name('keuangan.penagihan');
    
    // Riwayat Fee & Setoran
    Volt::route('/keuangan/riwayat-fee', 'lkbb.keuangan.riwayat-fee')->name('keuangan.riwayat-fee');
    
    // Approval Withdraw Dipisah
    Volt::route('/keuangan/approval-withdraw-merchant', 'lkbb.keuangan.withdraw-merchant-approval')->name('lkbb.withdraw.merchant.approval');
    Volt::route('/keuangan/approval-withdraw-pemasok', 'lkbb.keuangan.withdraw-pemasok-approval')->name('lkbb.withdraw.pemasok.approval');
    
    // Riwayat dan detail LKBB
    Volt::route('/lkbb/riwayat-approval', 'lkbb.riwayat.riwayat-approval-mahasiswa')->name('lkbb.riwayat');
    Volt::route('/lkbb/riwayat/mahasiswa/{id}', 'lkbb.riwayat.detail-mahasiswa')->name('lkbb.mahasiswa.detail');

    Route::get('/lkbb/monitoring-return', \App\Livewire\Lkbb::class . '\MonitoringReturn')->name('lkbb.monitoring-return');
    // ----------------------------------------------------------
    // MERCHANT ROUTES
    // ----------------------------------------------------------
    Volt::route('/merchant/dashboard', 'dashboard.merchant')->name('merchant.dashboard');
    Volt::route('/merchant/pos', 'merchant.pos-merchant')->name('merchant.pos');
    
    // ROUTE BARU: DAPUR PESANAN ONLINE
    Volt::route('/merchant/pesanan-online', 'merchant.pesanan-online')->name('merchant.pesanan-online');
    
    Volt::route('/merchant/withdraw', 'merchant.withdraw')->name('merchant.withdraw');
    Volt::route('/merchant/katalog', 'merchant.katalog')->name('merchant.katalog');
    Volt::route('/merchant/profile', 'merchant.profile')->name('merchant.profile');
    Volt::route('/merchant/application-status', 'merchant.application-status')->name('merchant.application-status');
    Volt::route('/merchant/order', 'merchant.order-bahan')->name('merchant.order');
    Volt::route('/merchant/riwayat', 'merchant.riwayat')->name('merchant.riwayat');
    Volt::route('/merchant/penerimaan', 'merchant.penerimaan')->name('merchant.penerimaan');
    Route::get('/merchant/riwayat-po', \App\Livewire\Merchant\RiwayatPo::class)->name('merchant.riwayat-po');
    Volt::route('/merchant/setoran', 'merchant.setoran')->name('merchant.setoran');
    
    // Sisi Merchant
    Route::get('/merchant/return/ajukan/{orderId}', \App\Livewire\Merchant\FormReturn::class)->name('merchant.form-return');
    // Top-up Merchant dihapus karena sekarang menggunakan skema LKBB Financing

    // ----------------------------------------------------------
    // PEMASOK ROUTES
    // ----------------------------------------------------------
    // Route Status Pengajuan (Bisa diakses meski belum di-approve)
    Volt::route('/pemasok/application-status', 'pemasok.application-status')->name('pemasok.application-status');
    // Berikan middleware tambahan atau pengecekan manual di component dashboard kamu agar jika belum approved tidak bisa tembus route bawah ini
    Volt::route('/pemasok/dashboard', 'dashboard.pemasok')->name('pemasok.dashboard');
    Route::get('/pemasok/inventaris', ManajemenProduk::class)->name('pemasok.inventaris');
    Route::get('/pemasok/profil', ProfilePemasok::class)->name('pemasok.profil');
    Route::get('/pemasok/laporan', LaporanAnalitik::class)->name('pemasok.laporan');
    Route::get('/pemasok/riwayat-produksi', RiwayatProduksi::class)->name('pemasok.riwayat-produksi');
    Route::get('/pemasok/pesanan-masuk', PesananMasuk::class)->name('pemasok.pesanan-masuk');
    Route::get('/pemasok/tarik-dana', TarikDana::class)->name('pemasok.tarik-dana');
    Route::get('/pemasok/pengiriman', PengirimanLogistik::class)->name('pemasok.pengiriman');
    // Pengajuan Dana LKBB (Pemasok) dihapus karena otomatis cair dari Approval PO Merchant
    Route::get('/pemasok/manajemen-return', \App\Livewire\Pemasok\ManajemenReturn::class)->name('pemasok.manajemen-return');

    
}); // PENUTUP MIDDLEWARE AUTH (Semua rute di atas aman!)

require __DIR__.'/auth.php';