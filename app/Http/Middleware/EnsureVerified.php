<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: pastikan user (merchant/pemasok) sudah diverifikasi LKBB
 * sebelum mengakses route bisnis. Kalau belum, REDIRECT ke pending-verification
 * (bukan abort 403 — UX buruk).
 *
 * Apply ke route group:
 *   Route::middleware(['auth', 'verified.entity'])->group(...);
 */
class EnsureVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admin + LKBB selalu lolos (mereka adalah verifikator).
        if (in_array($user->role, ['admin', 'lkbb'], true)) {
            return $next($request);
        }

        $profile = match ($user->role) {
            'merchant' => $user->merchantProfile,
            'pemasok'  => $user->pemasokProfile,
            default    => null,
        };

        $status = $profile?->status_verifikasi;

        // Loloskan kalau status = 'disetujui' (alias 'terverifikasi' di beberapa flow).
        if (in_array($status, ['disetujui', 'terverifikasi'], true)) {
            return $next($request);
        }

        // Belum disetujui → redirect ke dashboard role (yang punya status display + form onboarding).
        // Sidebar dashboard akan hide menu bisnis lain via conditional, jadi UX = "sidebar ada tapi cuma dashboard yang accessible".
        $redirectRoute = match ($user->role) {
            'merchant' => 'merchant.dashboard',
            'pemasok'  => 'pemasok.dashboard',
            default    => 'dashboard',
        };
        return redirect()->route($redirectRoute);
    }
}
