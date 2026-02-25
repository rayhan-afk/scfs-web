<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Mahasiswa
Volt::route('/admin/verifikasi-mahasiswa', 'admin.mahasiswa-verification')->name('admin.verification');
Volt::route('/admin/data-mahasiswa', 'admin.mahasiswa-data')->name('admin.mahasiswa.index');
Volt::route('/admin/data-mahasiswa/{id}', 'admin.mahasiswa-detail')->name('admin.mahasiswa.detail');

// Kantin / Merchant
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

Volt::route('/admin/monitoring-transaksi', 'admin.monitoring-transaksi')->name('admin.monitoring.index');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
