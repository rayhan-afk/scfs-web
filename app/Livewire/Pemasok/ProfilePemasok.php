<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\PemasokProfile;

class ProfilePemasok extends Component
{
    use WithFileUploads;

    // Profil & usaha
    public $nama_perusahaan;
    public $nama_pic;
    public $nik;
    public $no_hp;
    public $alamat;

    // Rekening (flat, sejajar MerchantProfile)
    public $nama_bank = 'BCA';
    public $bank_lainnya = '';
    public $no_rekening = '';
    public $atas_nama_rekening = '';

    // Dokumen
    public $existing_ktp;
    public $existing_gudang;
    public $foto_ktp_baru;
    public $foto_gudang_baru;

    // Keamanan (step-up auth) — gabung pola Merchant: ubah profil + rekening butuh password
    public $password_konfirmasi = '';

    // Ganti password (form terpisah)
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    public array $daftarBank = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BJB', 'GoPay', 'OVO', 'Lainnya'];

    public function mount()
    {
        $profile = PemasokProfile::where('user_id', Auth::id())->firstOrFail();

        $this->nama_perusahaan    = $profile->nama_perusahaan;
        $this->nama_pic           = $profile->nama_pic;
        $this->nik                = $profile->nik;
        $this->no_hp              = $profile->no_hp;
        $this->alamat             = $profile->alamat;
        $this->atas_nama_rekening = $profile->atas_nama_rekening;

        $standardBanks = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BJB', 'GoPay', 'OVO'];
        if (in_array($profile->nama_bank, $standardBanks)) {
            $this->nama_bank = $profile->nama_bank;
        } elseif (!empty($profile->nama_bank)) {
            $this->nama_bank = 'Lainnya';
            $this->bank_lainnya = $profile->nama_bank;
        }
        $this->no_rekening = $profile->no_rekening;

        $this->existing_ktp    = $profile->foto_ktp;
        $this->existing_gudang = $profile->foto_gudang;
    }

    public function simpanProfil()
    {
        $this->validate([
            'nama_perusahaan'    => 'required|string|max:255',
            'nama_pic'           => 'required|string|max:255',
            'nik'                => 'required|digits:16',
            'no_hp'              => 'required|string|max:20|regex:/^[0-9\-\+]+$/',
            'alamat'             => 'required|string|max:500',
            'nama_bank'          => 'required|string',
            'bank_lainnya'       => 'required_if:nama_bank,Lainnya|nullable|string|max:50',
            'no_rekening'        => 'required|numeric|digits_between:8,20',
            'atas_nama_rekening' => 'nullable|string|max:100',
            'foto_ktp_baru'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'foto_gudang_baru'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password_konfirmasi'=> 'required|string',
        ], [
            'password_konfirmasi.required' => 'Password wajib diisi untuk keamanan perubahan profil/rekening.',
            'bank_lainnya.required_if' => 'Wajib isi nama bank jika memilih "Lainnya".',
            'no_rekening.numeric' => 'Nomor rekening hanya boleh berisi angka.',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->password_konfirmasi, $user->password)) {
            throw ValidationException::withMessages([
                'password_konfirmasi' => 'Password yang Anda masukkan salah. Perubahan dibatalkan.',
            ]);
        }

        $profile = PemasokProfile::where('user_id', $user->id)->first();

        $bankFinal = $this->nama_bank === 'Lainnya' ? $this->bank_lainnya : $this->nama_bank;

        $updateData = [
            'nama_perusahaan'    => $this->nama_perusahaan,
            'nama_pic'           => $this->nama_pic,
            'nik'                => $this->nik,
            'no_hp'              => $this->no_hp,
            'alamat'             => $this->alamat,
            'nama_bank'          => $bankFinal,
            'no_rekening'        => $this->no_rekening,
            'atas_nama_rekening' => $this->atas_nama_rekening,
        ];

        if ($this->foto_ktp_baru) {
            if ($profile->foto_ktp && Storage::disk('public')->exists($profile->foto_ktp)) {
                Storage::disk('public')->delete($profile->foto_ktp);
            }
            $updateData['foto_ktp'] = $this->foto_ktp_baru->store('pemasok/ktp', 'public');
            $this->existing_ktp = $updateData['foto_ktp'];
        }
        if ($this->foto_gudang_baru) {
            if ($profile->foto_gudang && Storage::disk('public')->exists($profile->foto_gudang)) {
                Storage::disk('public')->delete($profile->foto_gudang);
            }
            $updateData['foto_gudang'] = $this->foto_gudang_baru->store('pemasok/gudang', 'public');
            $this->existing_gudang = $updateData['foto_gudang'];
        }

        $profile->update($updateData);
        $user->update(['name' => $this->nama_pic]);

        $this->reset(['foto_ktp_baru', 'foto_gudang_baru', 'password_konfirmasi']);
        session()->flash('success_profil', 'Data profil dan rekening berhasil diamankan & disimpan.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ], [
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini salah.',
            ]);
        }

        $user->update(['password' => Hash::make($this->new_password)]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success_password', 'Password akun berhasil diperbarui!');
    }

    public function render()
    {
        return view('livewire.pemasok.profile-pemasok')->layout('layouts.app');
    }
}
