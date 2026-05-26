<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    
    // 1. REGISTER
    // Mengarah ke file: resources/views/livewire/register.blade.php
    Volt::route('register', 'register')
        ->name('register');

    // 2. LOGIN
    // Mengarah ke file: resources/views/livewire/login.blade.php
    Volt::route('login', 'login')
        ->name('login');

    // 3. FITUR LUPA PASSWORD (Bawaan Breeze)
    // Biarkan mengarah ke pages.auth karena kita belum mengubah desain ini
    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    
    // 4. VERIFIKASI EMAIL
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // 5. KONFIRMASI PASSWORD
    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
        
    // Catatan: Tombol logout di sidebar dashboard tetap pakai wire:click="logout"
    // (Action class). Route POST `logout` di bawah ini untuk halaman non-Livewire
    // (mis. pending-verification, error pages) yang butuh form submit standar.
    Route::post('logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});