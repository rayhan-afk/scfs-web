<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MahasiswaAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Route Publik (Tidak butuh token)
Route::post('/login', [MahasiswaAuthController::class, 'login']);

// Route Terproteksi (Wajib bawa Token dari Flutter)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [MahasiswaAuthController::class, 'profile']);
    Route::post('/logout', [MahasiswaAuthController::class, 'logout']);
    Route::get('/generate-qr', [MahasiswaAuthController::class, 'generateQr']);
    Route::post('/update-avatar', [MahasiswaAuthController::class, 'updateAvatar']);
    Route::post('/update-profile', [MahasiswaAuthController::class, 'updateProfile']);
    Route::get('/transactions', [MahasiswaAuthController::class, 'transactions']);
});