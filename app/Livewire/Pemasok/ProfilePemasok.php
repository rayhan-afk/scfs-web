<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithFileUploads; // TAMBAH INI UNTUK UPLOAD FILE
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\SupplierProfile;

class ProfilePemasok extends Component
{
    use WithFileUploads; // Wajib untuk handle file upload

    // Navigasi Tab & UI State
    public $activeTab = 'informasi';
    public $isEditing = false;
    public $showRekeningModal = false;

    // Data Form Informasi
    public $nama_usaha;
    public $no_hp;
    public $alamat_gudang;

    // Data Form Rekening
    public $info_rekening;
    public $password_konfirmasi;
    
    // Pecahan untuk Modal Ubah Rekening
    public $nama_bank_baru;
    public $nomor_rekening_baru;
    public $daftar_bank = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Permata'];

    // Data Form Dokumen
    public $foto_ktp_lama;
    public $foto_usaha_lama;
    public $foto_ktp_baru;
    public $foto_usaha_baru;

    // Data Form Keamanan
    public $current_password;
    public $password;
    public $password_confirmation;
    public function mount()
    {
        $user = Auth::user();
        $profil = SupplierProfile::where('user_id', $user->id)->first();

        if ($profil) {
            $this->nama_usaha = $profil->nama_usaha ?? '';
            $this->no_hp = $profil->no_hp ?? '';
            $this->alamat_gudang = $profil->alamat_gudang ?? '';
            $this->info_rekening = $profil->info_rekening ?? '';
            
            // Muat gambar lama
            $this->foto_ktp_lama = $profil->foto_ktp ?? null;
            $this->foto_usaha_lama = $profil->foto_usaha ?? null;
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->isEditing = false; 
    }

    public function toggleEdit()
    {
        $this->isEditing = !$this->isEditing;
    }

    public function simpanInformasi()
    {
        $this->validate([
            'nama_usaha' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'alamat_gudang' => 'required|string',
        ]);

        $user = Auth::user();
        SupplierProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_usaha' => $this->nama_usaha,
                'no_hp' => $this->no_hp,
                'alamat_gudang' => $this->alamat_gudang,
            ]
        );

        session()->flash('message', 'Informasi Usaha berhasil diperbarui!');
        return redirect()->route('pemasok.profil'); 
    }

    public function simpanDokumen()
    {
        $this->validate([
            'foto_ktp_baru' => 'nullable|image|max:2048', // Maksimum 2MB
            'foto_usaha_baru' => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        $profil = SupplierProfile::where('user_id', $user->id)->first();
        $updateData = [];

        // Jika ada KTP baru di-upload
        if ($this->foto_ktp_baru) {
            // (Pilihan) Boleh padam fail lama jika mahu jimat storage
            // if ($this->foto_ktp_lama) Storage::disk('public')->delete($this->foto_ktp_lama);
            
            $pathKtp = $this->foto_ktp_baru->store('suppliers/ktp', 'public');
            $updateData['foto_ktp'] = $pathKtp;
            $this->foto_ktp_lama = $pathKtp; // Kemaskini preview
        }

        // Jika ada Foto Usaha baru di-upload
        if ($this->foto_usaha_baru) {
            $pathUsaha = $this->foto_usaha_baru->store('suppliers/usaha', 'public');
            $updateData['foto_usaha'] = $pathUsaha;
            $this->foto_usaha_lama = $pathUsaha; // Kemaskini preview
        }

        if (!empty($updateData)) {
            $profil->update($updateData);
            session()->flash('message', 'Dokumen terbaru berhasil disimpan!');
        }

        // Reset input file
        $this->reset(['foto_ktp_baru', 'foto_usaha_baru']);
    }

    public function ubahRekening()
    {
        $this->validate([
            'password_konfirmasi' => 'required',
            'nama_bank_baru' => 'required',
            'nomor_rekening_baru' => 'required|numeric',
        ]);

        $user = Auth::user();

        // Semak password
        if (!Hash::check($this->password_konfirmasi, $user->password)) {
            $this->addError('password_konfirmasi', 'Kata sandi anda salah.');
            return;
        }

        // Gabungkan bank dan nombor
        $rekening_gabungan = $this->nama_bank_baru . ' - ' . $this->nomor_rekening_baru;

        SupplierProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['info_rekening' => $rekening_gabungan]
        );

        $this->info_rekening = $rekening_gabungan;
        $this->showRekeningModal = false;
        $this->reset(['password_konfirmasi', 'nama_bank_baru', 'nomor_rekening_baru']);
        
        session()->flash('message', 'Akaun pencairan dana berhasil diganti');
    }
    public function updatePassword()
    {
        // Validasi input password
        $this->validate([
            'current_password' => ['required', 'current_password'], // Memastikan password lama benar
            'password' => ['required', 'min:8', 'confirmed'],       // Password baru min 8 karakter & cocok dengan konfirmasi
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal harus 8 karakter.'
        ]);

        $user = Auth::user();
        
        // Update password di tabel users (bukan SupplierProfile)
        $user->update([
            'password' => Hash::make($this->password),
        ]);

        // Kosongkan form setelah berhasil
        $this->reset(['current_password', 'password', 'password_confirmation']);
        
        session()->flash('message', 'Kata sandi akun berhasil diperbarui!');
    }

    public function render()
    {
        return view('livewire.pemasok.profile-pemasok')->layout('layouts.app'); 
    }
}