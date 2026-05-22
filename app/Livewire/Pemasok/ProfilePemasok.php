<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithFileUploads; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\SupplierProfile;

class ProfilePemasok extends Component
{
    use WithFileUploads; 

    // Navigasi Tab & UI State
    public $activeTab = 'informasi';
    public $isEditing = false;
    public $showRekeningModal = false;

    // Data Form Informasi (PERBAIKAN: Menambahkan nama_pemilik dan nik)
    public $nama_usaha;
    public $nama_pemilik;
    public $nik;
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
            $this->nama_pemilik = $profil->nama_pemilik ?? ''; // PERBAIKAN: Load dari DB
            $this->nik = $profil->nik ?? '';                   // PERBAIKAN: Load dari DB
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
            'nama_usaha'    => 'required|string|max:255',
            'nama_pemilik'  => 'required|string|max:255',
            'nik'           => 'required|string|max:20',
            'no_hp'         => 'required|string|max:20',
            'alamat_gudang' => 'required|string',
        ]);

        $user = Auth::user();

        SupplierProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_usaha'        => $this->nama_usaha,
                'nama_pemilik'      => $this->nama_pemilik,
                'nik'               => $this->nik,
                'no_hp'             => $this->no_hp,
                'alamat_gudang'     => $this->alamat_gudang,
                'status_verifikasi' => 'menunggu_review', // ← INI yang bikin muncul di admin!
            ]
        );

        $this->isEditing = false;
        session()->flash('message', 'Informasi usaha berhasil diperbarui.');
    }

    public function simpanDokumen()
    {
        $this->validate([
            'foto_ktp_baru' => 'nullable|image|max:2048', 
            'foto_usaha_baru' => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        $profil = SupplierProfile::where('user_id', $user->id)->first();
        
        // PENTING: Pastikan status ikut ter-update menjadi menunggu review saat upload ulang dokumen
        $updateData = [
            'status_verifikasi' => 'menunggu_review' 
        ];

        // Jika ada KTP baru di-upload
        if ($this->foto_ktp_baru) {
            $pathKtp = $this->foto_ktp_baru->store('suppliers/ktp', 'public');
            $updateData['foto_ktp'] = $pathKtp;
            $this->foto_ktp_lama = $pathKtp; 
        }

        // Jika ada Foto Usaha baru di-upload
        if ($this->foto_usaha_baru) {
            $pathUsaha = $this->foto_usaha_baru->store('suppliers/usaha', 'public');
            $updateData['foto_usaha'] = $pathUsaha;
            $this->foto_usaha_lama = $pathUsaha; 
        }

        if ($profil) {
            $profil->update($updateData);
            session()->flash('message', 'Dokumen terbaru berhasil disimpan untuk direview Admin!');
        }

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

        if (!Hash::check($this->password_konfirmasi, $user->password)) {
            $this->addError('password_konfirmasi', 'Kata sandi anda salah.');
            return;
        }

        $rekening_array = [
            'nama_bank' => $this->nama_bank_baru,
            'nomor_rekening' => $this->nomor_rekening_baru,
        ];

        SupplierProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'info_rekening' => $rekening_array,
                'status_verifikasi' => 'menunggu_review' // Set status review
            ]
        );

        $this->info_rekening = $rekening_array;
        $this->showRekeningModal = false;
        $this->reset(['password_konfirmasi', 'nama_bank_baru', 'nomor_rekening_baru']);
        
        session()->flash('message', 'Akun pencairan dana berhasil diganti dan diajukan kembali.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'], 
            'password' => ['required', 'min:8', 'confirmed'], 
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal harus 8 karakter.'
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('message', 'Kata sandi akun berhasil diperbarui!');
    }

    public function render()
    {
        return view('livewire.pemasok.profile-pemasok')->layout('layouts.app'); 
    }
}