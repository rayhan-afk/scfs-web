<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Volt::route('/admin/verifikasi-mahasiswa', 'admin.mahasiswa-verification')
    ->name('admin.verification');

Volt::route('/admin/data-mahasiswa', 'admin.mahasiswa-data')
    ->name('admin.mahasiswa.index');

Volt::route('/admin/data-mahasiswa/{id}', 'admin.mahasiswa-detail')
    ->name('admin.mahasiswa.detail');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
