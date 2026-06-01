<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MahasiswaResource;
use App\Http\Requests\LoginMahasiswaRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\MahasiswaProfile;
use App\Models\MerchantProfile;
use App\Models\Transaction;

class MahasiswaAuthController extends Controller
{
    /**
     * 1. LOGIN API
     */
    public function login(LoginMahasiswaRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password) || $user->role !== 'mahasiswa') {
            return response()->json([
                'status' => 'error',
                'message' => 'Email/Password salah atau Anda bukan Mahasiswa.',
            ], 401);
        }

        DB::table('login_logs')->insert([
            'user_id'    => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at'   => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('flutter-mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login Berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ]
        ], 200);
    }

    /**
     * 2. GET PROFILE API
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data'   => new MahasiswaResource($user)
        ], 200);
    }

    /**
     * 3. LOGOUT API
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil Logout, token dihapus.'
        ], 200);
    }

    /**
     * 4. PAY QR (Fitur Scan Mahasiswa ke Layar Kantin)
     * Ini dipanggil oleh Flutter saat mahasiswa men-scan QR yang muncul di laptop Ibu Kantin.
     */
    /**
     * 4. PAY QR (Fitur Scan Mahasiswa ke Layar Kantin)
     */
    public function payQr(Request $request)
    {
        $request->validate([
            'order_id'    => 'required|string',
            'merchant_id' => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $mahasiswa = $request->user();
                $profileMhs = MahasiswaProfile::where('user_id', $mahasiswa->id)
                                ->lockForUpdate()
                                ->firstOrFail();

                // Cari transaksi PENDING yang dibuat mesin kasir + belum expired (15 menit).
                $trx = Transaction::where('order_id', $request->order_id)
                        ->where('status', 'pending')
                        ->where('created_at', '>', now()->subMinutes(15))
                        ->lockForUpdate()
                        ->first();

                if (!$trx) {
                    throw new \Exception('Transaksi tidak ditemukan atau kadaluwarsa. Minta Ibu Kantin me-refresh mesin kasir.');
                }

                // Verifikasi merchant_id payload QR sesuai dengan transaksi (anti QR bocor lintas merchant).
                if ((int) $trx->merchant_id !== (int) $request->merchant_id) {
                    throw new \Exception('QR tidak valid untuk merchant ini.');
                }

                if ($profileMhs->saldo < $trx->total_amount) {
                    throw new \Exception('Saldo beasiswa Anda tidak mencukupi untuk transaksi ini.');
                }

                $profileMhs->decrement('saldo', $trx->total_amount);

                // Split payment SCFS — hak LKBB (pokok+fee) & hak merchant (profit-fee).
                $hakLkbb     = $trx->total_pokok + $trx->fee_lkbb;
                $hakMerchant = ($trx->total_amount - $trx->total_pokok) - $trx->fee_lkbb;

                $merchantProfile = MerchantProfile::where('user_id', $trx->merchant_id)
                                    ->lockForUpdate()
                                    ->firstOrFail();
                $merchantProfile->increment('saldo_token', $hakMerchant);

                // Wallet LKBB_OPERATIONAL adalah konfigurasi infra. Tidak boleh auto-create.
                $walletOperasional = \App\Models\Wallet::where('type', 'LKBB_OPERATIONAL')
                                        ->lockForUpdate()
                                        ->first();

                if (!$walletOperasional) {
                    throw new \Exception('Wallet LKBB_OPERATIONAL belum dikonfigurasi. Hubungi admin.');
                }

                $walletOperasional->increment('balance', $hakLkbb);

                // Audit ledger: dana realisasi ke LKBB dari pembayaran QR.
                \App\Models\LedgerEntry::create([
                    'transaction_id' => $trx->id,
                    'wallet_id'      => $walletOperasional->id,
                    'entry_type'     => 'CREDIT',
                    'amount'         => $hakLkbb,
                    'balance_after'  => $walletOperasional->fresh()->balance,
                ]);

                $trx->update([
                    'user_id' => $mahasiswa->id,
                    'status'  => 'sukses'
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran Berhasil! Silakan ambil makanan Anda.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 5. GET TRANSACTION HISTORY API
     */
    public function transactions(Request $request)
    {
        $transactions = Transaction::with('merchant.merchantProfile')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['sukses', 'lunas'])
            ->where('type', 'pembayaran_makanan') 
            ->latest()
            ->paginate(15); 

        $formattedData = $transactions->getCollection()->map(function ($trx) {
            $namaKantin = $trx->merchant->merchantProfile->nama_kantin 
                          ?? $trx->merchant->name 
                          ?? 'Kantin Tidak Diketahui';

            return [
                'order_id'    => $trx->order_id,
                'waktu'       => $trx->created_at->format('d M Y, H:i'),
                'timestamp'   => $trx->created_at->timestamp, 
                'nama_kantin' => $namaKantin,
                'deskripsi'   => str_replace('[QR] ', '', $trx->description), 
                'nominal'     => (int) $trx->total_amount, 
                'status'      => $trx->status,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data riwayat transaksi berhasil diambil',
            'data' => $formattedData,
            'meta' => [
                'current_page'   => $transactions->currentPage(),
                'last_page'      => $transactions->lastPage(),
                'has_more_pages' => $transactions->hasMorePages(),
                'total_data'     => $transactions->total(),
            ]
        ], 200);
    }

    /**
     * 6. UPDATE AVATAR API
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();
        $profile = $user->mahasiswaProfile;

        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mahasiswa tidak ditemukan.'
            ], 404);
        }

        try {
            if ($profile->ktm_image && Storage::disk('public')->exists($profile->ktm_image)) {
                Storage::disk('public')->delete($profile->ktm_image);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $profile->ktm_image = $path;
            $profile->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Foto berhasil diperbarui',
                'data' => [
                    'avatar_url' => asset('storage/' . $path)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 7. UPDATE PROFILE API
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'no_hp'  => 'required|string|max:20',
            'alamat' => 'required|string',
        ]);

        $user = $request->user();
        $profile = $user->mahasiswaProfile;
        
        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data profil belum lengkap, hubungi admin.'
            ], 404);
        }

        $profile->no_hp = $request->no_hp;
        $profile->alamat = $request->alamat;
        $profile->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui'
        ], 200);
    }
}