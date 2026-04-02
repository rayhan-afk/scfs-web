<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MahasiswaResource;
use App\Http\Requests\LoginMahasiswaRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class MahasiswaAuthController extends Controller
{
    /**
     * 1. LOGIN API (Dari Flutter mengirim Email & Password)
     */
    // PERHATIKAN: Parameter berubah dari (Request $request) menjadi (LoginMahasiswaRequest $request)
    public function login(LoginMahasiswaRequest $request)
    {
        // ❌ BLOK INI SUDAH DIHAPUS KARENA SUDAH DIURUS OLEH SATPAM:
        // $request->validate([
        //     'email' => 'required|email',
        //     'password' => 'required',
        // ]);
        
        // --- MANAJER LANGSUNG BEKERJA (Logika Bisnis) ---
        $user = User::where('email', $request->email)->first();

        // Cek apakah user ada, password benar, dan rolenya mahasiswa
        if (!$user || !Hash::check($request->password, $user->password) || $user->role !== 'mahasiswa') {
            return response()->json([
                'status' => 'error',
                'message' => 'Email/Password salah atau Anda bukan Mahasiswa.',
            ], 401);
        }

        // Catat ke tabel riwayat login
        \Illuminate\Support\Facades\DB::table('login_logs')->insert([
            'user_id'    => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at'   => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Buat Token Sanctum
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
     * 2. GET PROFILE API (Untuk nampilin data di layar Home Flutter)
     * Ini diproteksi, harus pakai Token.
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            // Kita serahkan data mentah $user ke Koki Plating (MahasiswaResource)
            'data'   => new MahasiswaResource($user)
        ], 200);
    }

    /**
     * 3. LOGOUT API (Hapus token dari HP & Server)
     */
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil Logout, token dihapus.'
        ], 200);
    }

    /**
     * 4. GENERATE QR CODE API (Anti-Screenshot / Time-based)
     */
    public function generateQr(Request $request)
    {
        $user = $request->user();
        
        // Pastikan relasi profil mahasiswa di-load
        $user->load('mahasiswaProfile');
        $profile = $user->mahasiswaProfile;

        // Validasi: Apakah akun sudah diverifikasi oleh Admin LKBB?
        if (!$profile || $profile->status_verifikasi !== 'disetujui') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun belum diverifikasi atau profil tidak ditemukan. Anda belum bisa generate QR.'
            ], 403);
        }

        // Validasi: Apakah saldo cukup untuk jajan? (Opsional, tapi bagus untuk UX)
        if ($profile->saldo <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo Anda Rp 0. Silakan isi ulang / tunggu pencairan beasiswa.'
            ], 400);
        }

        // Buat Payload Data
        $payload = [
            'user_id' => $user->id,
            'nim'     => $profile->nim,
            'exp'     => now()->addMinutes(2)->timestamp // Hanya valid 2 menit dari sekarang
        ];

        // Enkripsi payload menjadi string rahasia menggunakan APP_KEY Laravel
        // Hanya server Laravel kita yang bisa membuka gembok enkripsi ini nanti di mesin POS
        $qrString = \Illuminate\Support\Facades\Crypt::encryptString(json_encode($payload));

        return response()->json([
            'status' => 'success',
            'message' => 'QR Code berhasil di-generate',
            'data' => [
                'qr_string' => $qrString,
                'valid_for_seconds' => 120, // Beri tahu Flutter untuk bikin timer mundur 2 menit
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]
        ], 200);
    }

    /**
     * 5. GET TRANSACTION HISTORY API (Riwayat Jajan Mahasiswa)
     */
    public function transactions(Request $request)
    {
        // 1. Eager Loading & Strict Authorization (Hanya tarik data milik user yang sedang login)
        $transactions = \App\Models\Transaction::with('merchant.merchantProfile')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['sukses', 'lunas'])
            // Pastikan hanya menampilkan transaksi digital (QR), bukan tunai orang lain
            ->where('type', 'pembayaran_makanan') 
            ->latest()
            ->paginate(15); // Ambil 15 data per halaman untuk Infinite Scroll Flutter

        // 2. Data Transformation (Membuang data sensitif merchant seperti Fee LKBB)
        $formattedData = $transactions->getCollection()->map(function ($trx) {
            
            // Mencari nama kantin (Fallback ke nama user jika profil kantin belum lengkap)
            $namaKantin = $trx->merchant->merchantProfile->nama_kantin 
                          ?? $trx->merchant->name 
                          ?? 'Kantin Tidak Diketahui';

            return [
                'order_id'    => $trx->order_id,
                'waktu'       => $trx->created_at->format('d M Y, H:i'),
                'timestamp'   => $trx->created_at->timestamp, // Untuk sorting akurat di sisi Flutter
                'nama_kantin' => $namaKantin,
                'deskripsi'   => str_replace('[QR] ', '', $trx->description), // Bersihkan teks '[QR]'
                'nominal'     => (int) $trx->total_amount, // Cast ke integer agar di Flutter dibaca sebagai int, bukan string
                'status'      => $trx->status,
            ];
        });

        // 3. Return JSON dengan Metadata Pagination
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
     * 6. UPDATE AVATAR API (Simpan ke kolom ktm_image di mahasiswa_profiles)
     */
    public function updateAvatar(Request $request)
    {
        // 1. Validasi Input dari Flutter
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();
        
        // KUNCI UTAMA: Kita panggil tabel profil mahasiswanya
        $profile = $user->mahasiswaProfile;

        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mahasiswa tidak ditemukan.'
            ], 404);
        }

        try {
            // 2. Jika sebelumnya sudah ada gambar di ktm_image, hapus agar storage tidak bengkak
            if ($profile->ktm_image && Storage::disk('public')->exists($profile->ktm_image)) {
                Storage::disk('public')->delete($profile->ktm_image);
            }

            // 3. Simpan file gambar baru ke folder 'avatars' di public storage
            $path = $request->file('avatar')->store('avatars', 'public');

            // 4. Update kolom 'ktm_image' di tabel 'mahasiswa_profiles'
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
     * 7. UPDATE PROFILE API (Ubah No HP & Alamat)
     */
    public function updateProfile(Request $request)
    {
        // Validasi input dari Flutter
        $request->validate([
            'no_hp'  => 'required|string|max:20',
            'alamat' => 'required|string',
        ]);

        $user = $request->user();
        
        // Pastikan tabel profil mahasiswa terhubung
        $profile = $user->mahasiswaProfile;
        
        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data profil belum lengkap, hubungi admin.'
            ], 404);
        }

        // Update data di database
        $profile->no_hp = $request->no_hp;
        $profile->alamat = $request->alamat;
        $profile->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui'
        ], 200);
    }
}