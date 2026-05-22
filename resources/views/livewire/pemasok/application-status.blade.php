<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\SupplierProfile;
use App\Notifications\NewSupplierSubmission;

new #[Layout('layouts.app')]
class extends Component {
    use WithFileUploads;

    // State UI
    public $profile;
    public $status;

    // Form fields
    public $nama_usaha;
    public $nama_pemilik;
    public $nik;
    public $no_hp;
    public $alamat_gudang;

    // Rekening
    public $nama_bank;
    public $nomor_rekening;
    public string $selected_bank = '';
    public string $nama_bank_lain = '';
    public array $daftar_bank = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Bank Jago', 'SeaBank', 'Bank Lain'];

    // Dokumen
    public $foto_ktp;
    public $foto_usaha;

    public function mount()
    {
        $user = Auth::user();
        $this->profile = SupplierProfile::where('user_id', $user->id)->first();
        $this->status = $this->profile?->status_verifikasi;

        if ($this->profile) {
            $this->nama_usaha    = $this->profile->nama_usaha;
            $this->nama_pemilik  = $this->profile->nama_pemilik;
            $this->nik           = $this->profile->nik;
            $this->no_hp         = $this->profile->no_hp;
            $this->alamat_gudang = $this->profile->alamat_gudang;

            if ($this->profile->info_rekening) {
                $this->nama_bank      = $this->profile->nama_bank;
                $this->nomor_rekening = $this->profile->nomor_rekening;
                $this->selected_bank  = $this->profile->nama_bank ?? '';
            }
        }
    }

    public function kirimPengajuan()
    {
        $this->validate([
            'nama_usaha'    => 'required|string|max:255',
            'nama_pemilik'  => 'required|string|max:255',
            'nik'           => 'required|string|max:20',
            'no_hp'         => 'required|string|max:20',
            'alamat_gudang' => 'required|string',
            'selected_bank' => 'required|string',
            'nomor_rekening'=> 'required|numeric',
            'foto_ktp'      => 'required|image|max:2048',
            'foto_usaha'    => 'nullable|image|max:2048',
        ], [
            'nama_usaha.required'    => 'Nama perusahaan/usaha wajib diisi.',
            'nama_pemilik.required'  => 'Nama pemilik wajib diisi.',
            'nik.required'           => 'NIK wajib diisi.',
            'no_hp.required'         => 'Nomor HP wajib diisi.',
            'alamat_gudang.required' => 'Alamat gudang wajib diisi.',
            'selected_bank.required' => 'Nama bank wajib dipilih.',
            'nomor_rekening.required'=> 'Nomor rekening wajib diisi.',
            'foto_ktp.required'      => 'Foto KTP wajib diunggah.',
        ]);

        $user = Auth::user();

        // Tentukan nama bank final (jika "Bank Lain" dipilih, pakai input manual)
        $namaBankFinal = $this->selected_bank === 'Bank Lain'
            ? $this->nama_bank_lain
            : $this->selected_bank;

        // Simpan foto KTP
        $pathKtp = $this->foto_ktp->store('suppliers/ktp', 'public');

        // Simpan foto usaha jika ada
        $pathUsaha = null;
        if ($this->foto_usaha) {
            $pathUsaha = $this->foto_usaha->store('suppliers/usaha', 'public');
        }

        // Simpan atau update ke SupplierProfile
        $profile = SupplierProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_usaha'        => $this->nama_usaha,
                'nama_pemilik'      => $this->nama_pemilik,
                'nik'               => $this->nik,
                'no_hp'             => $this->no_hp,
                'alamat_gudang'     => $this->alamat_gudang,
                'info_rekening'     => [
                    'nama_bank'      => $namaBankFinal,
                    'nomor_rekening' => $this->nomor_rekening,
                ],
                'foto_ktp'          => $pathKtp,
                'foto_usaha'        => $pathUsaha,
                'status_verifikasi' => 'menunggu_review',
            ]
        );

        // Kirim notifikasi ke semua user LKBB
        $lkbbUsers = \App\Models\User::where('role', 'lkbb')->get();
        foreach ($lkbbUsers as $lkbb) {
            $lkbb->notify(new NewSupplierSubmission($profile));
        }

        $this->profile = $profile;
        $this->status  = 'menunggu_review';

        session()->flash('message', 'Pengajuan berhasil dikirim! Tim LKBB akan memverifikasi data Anda.');
    }

}; ?>

<div class="max-w-5xl mx-auto py-8 px-4">

    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-2xl flex items-center gap-3">
            <span class="text-xl">✅</span>
            <span class="font-medium text-sm">{{ session('message') }}</span>
        </div>
    @endif

    {{-- ===================== HEADER ===================== --}}
    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-800">Pemasok Dashboard</h1>
        <p class="text-gray-500 mt-1">Realtime SCFS Notification Center</p>
    </div>

    {{-- ===================== JIKA SUDAH DISETUJUI ===================== --}}
    @if($status === 'disetujui')
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-green-100 flex items-center justify-center text-3xl">✅</div>
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Akun Pemasok Aktif</h2>
                    <p class="text-gray-500 mt-1">Anda sudah terdaftar dan dapat menggunakan seluruh fitur pemasok.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4">Checklist Dokumen</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span>Foto KTP</span>
                            <span>{{ $profile?->foto_ktp ? '✅' : '❌' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Foto Gudang/Usaha</span>
                            <span>{{ $profile?->foto_usaha ? '✅' : '❌' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>NIK</span>
                            <span>{{ $profile?->nik ? '✅' : '❌' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>No HP</span>
                            <span>{{ $profile?->no_hp ? '✅' : '❌' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Rekening</span>
                            <span>{{ $profile?->info_rekening ? '✅' : '❌' }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4">Informasi Usaha</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span>Nama Usaha</span>
                            <span class="font-semibold">{{ $profile?->nama_usaha ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Pemilik</span>
                            <span class="font-semibold">{{ $profile?->nama_pemilik ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Alamat Gudang</span>
                            <span class="font-semibold text-right max-w-[60%] line-clamp-2">{{ $profile?->alamat_gudang ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- ===================== JIKA DITOLAK ===================== --}}
    @elseif($status === 'ditolak')
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 mb-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-red-100 flex items-center justify-center text-3xl">❌</div>
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Pengajuan Ditolak</h2>
                    <p class="text-gray-500 mt-1">Silakan perbaiki data dan kirim ulang pengajuan Anda.</p>
                </div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5 mb-6">
                <h3 class="font-bold text-red-700 mb-2">Alasan Penolakan</h3>
                <p class="text-sm text-red-600 leading-relaxed">{{ $profile->catatan_penolakan ?? 'Tidak ada catatan.' }}</p>
            </div>
        </div>
        {{-- Form Revisi Pengajuan --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8">
                <h2 class="text-xl font-black text-gray-800 mb-1">Revisi Pengajuan</h2>
                <p class="text-gray-500 mb-8 text-sm">Perbaiki data di bawah ini lalu kirim ulang pengajuan Anda.</p>

                <form wire:submit.prevent="kirimPengajuan" class="space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nama Perusahaan / Usaha</label>
                            <input type="text" wire:model="nama_usaha" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm" placeholder="Cth: Grosir Sumber Makmur">
                            @error('nama_usaha') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Alamat Gudang / Tempat Usaha</label>
                            <input type="text" wire:model="alamat_gudang" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm" placeholder="Jl. Contoh No. 1, Kota">
                            @error('alamat_gudang') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nama Pemilik (Sesuai KTP)</label>
                            <input type="text" wire:model="nama_pemilik" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm">
                            @error('nama_pemilik') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nomor Induk Kependudukan (NIK)</label>
                            <input type="text" wire:model="nik" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm">
                            @error('nik') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">No Handphone / WA Aktif</label>
                            <input type="text" wire:model="no_hp" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm">
                            @error('no_hp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-orange-600 uppercase tracking-widest mb-2">Rekening Penerimaan Dana</label>
                            <div class="flex gap-2">
                                <select wire:model.live="selected_bank" class="rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm w-36">
                                    <option value="">Pilih Bank</option>
                                    @foreach($daftar_bank as $bank)
                                        <option value="{{ $bank }}">{{ $bank }}</option>
                                    @endforeach
                                </select>
                                <input type="text" wire:model="nomor_rekening" class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm" placeholder="Nomor rekening">
                            </div>
                            @if($selected_bank === 'Bank Lain')
                                <input type="text" wire:model="nama_bank_lain" class="mt-2 w-full rounded-xl border-gray-200 shadow-sm py-3 text-sm" placeholder="Nama bank Anda">
                            @endif
                            @error('selected_bank') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            @error('nomor_rekening') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center hover:border-orange-300 transition-colors">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Upload Foto KTP *</label>
                            @if($foto_ktp)
                                <img src="{{ $foto_ktp->temporaryUrl() }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm mb-2">
                                <span class="text-[11px] text-green-600 font-bold block">✓ Tersimpan</span>
                            @elseif($profile?->foto_ktp)
                                <img src="{{ asset('storage/' . $profile->foto_ktp) }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm mb-2">
                                <span class="text-[11px] text-blue-500 block">Foto KTP saat ini</span>
                            @endif
                            <input type="file" wire:model="foto_ktp" id="foto_ktp_rev" class="hidden" accept="image/*">
                            <label for="foto_ktp_rev" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition mt-2">
                                Ganti KTP
                            </label>
                            @error('foto_ktp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center hover:border-orange-300 transition-colors">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Upload Foto Gudang / Usaha *</label>
                            @if($foto_usaha)
                                <img src="{{ $foto_usaha->temporaryUrl() }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm mb-2">
                                <span class="text-[11px] text-green-600 font-bold block">✓ Tersimpan</span>
                            @elseif($profile?->foto_usaha)
                                <img src="{{ asset('storage/' . $profile->foto_usaha) }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm mb-2">
                                <span class="text-[11px] text-blue-500 block">Foto gudang saat ini</span>
                            @endif
                            <input type="file" wire:model="foto_usaha" id="foto_usaha_rev" class="hidden" accept="image/*">
                            <label for="foto_usaha_rev" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition mt-2">
                                Ganti Foto Usaha
                            </label>
                            @error('foto_usaha') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-green-100 transition">
                            <div wire:loading wire:target="kirimPengajuan">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </div>
                            Kirim Ulang Pengajuan
                        </button>
                    </div>

                </form>
            </div>
        </div>

    {{-- ===================== JIKA SEDANG REVIEW / BELUM DAFTAR ===================== --}}
    @elseif($status === 'menunggu_review')
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-yellow-100 flex items-center justify-center text-3xl">⏳</div>
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Menunggu Verifikasi</h2>
                    <p class="text-gray-500 mt-1">Tim LKBB sedang memverifikasi data usaha Anda. Harap tunggu.</p>
                </div>
            </div>
        </div>

    {{-- ===================== FORM PENGAJUAN PERTAMA ===================== --}}
    @else
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-800 mb-1">Selamat Datang di Mitra Pemasok SCFS!</h2>
                <p class="text-gray-500 mb-8">Sebelum mulai menyuplai barang ke kantin, mohon lengkapi profil usaha Anda.</p>

                <form wire:submit.prevent="kirimPengajuan" class="space-y-6">

                    {{-- Baris 1: Nama Usaha & Alamat --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nama Perusahaan / Usaha</label>
                            <input type="text" wire:model="nama_usaha"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm"
                                placeholder="Cth: Grosir Sumber Makmur">
                            @error('nama_usaha') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Alamat Gudang / Tempat Usaha</label>
                            <input type="text" wire:model="alamat_gudang"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm"
                                placeholder="Jl. Contoh No. 1, Kota">
                            @error('alamat_gudang') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Baris 2: Nama Pemilik & NIK --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nama Pemilik (Sesuai KTP)</label>
                            <input type="text" wire:model="nama_pemilik"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm"
                                placeholder="Nama lengkap sesuai KTP">
                            @error('nama_pemilik') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nomor Induk Kependudukan (NIK)</label>
                            <input type="text" wire:model="nik"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm"
                                placeholder="16 digit NIK">
                            @error('nik') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Baris 3: No HP & Rekening --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">No Handphone / WA Aktif</label>
                            <input type="text" wire:model="no_hp"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm"
                                placeholder="08xxxxxxxxxx">
                            @error('no_hp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-orange-600 uppercase tracking-widest mb-2">Rekening Penerimaan Dana</label>
                            <div class="flex gap-2">
                                <select wire:model.live="selected_bank"
                                    class="rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm w-36">
                                    <option value="">Pilih Bank</option>
                                    @foreach($daftar_bank as $bank)
                                        <option value="{{ $bank }}">{{ $bank }}</option>
                                    @endforeach
                                </select>
                                <input type="text" wire:model="nomor_rekening"
                                    class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm"
                                    placeholder="Nomor rekening">
                            </div>
                            @error('selected_bank') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            @error('nomor_rekening') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror

                            {{-- Input nama bank manual jika pilih "Bank Lain" --}}
                            @if($selected_bank === 'Bank Lain')
                                <input type="text" wire:model="nama_bank_lain"
                                    class="mt-2 w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm"
                                    placeholder="Nama bank Anda">
                            @endif
                        </div>
                    </div>

                    {{-- Baris 4: Upload Foto --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Upload KTP --}}
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center hover:border-orange-300 transition-colors">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Upload Foto KTP *</label>
                            @if($foto_ktp)
                                <div class="mb-3">
                                    <img src="{{ $foto_ktp->temporaryUrl() }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm">
                                    <span class="text-[11px] text-green-600 font-bold mt-1 block">✓ Tersimpan</span>
                                </div>
                            @else
                                <div class="mb-3 text-gray-300">
                                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                            <input type="file" wire:model="foto_ktp" id="foto_ktp" class="hidden" accept="image/*">
                            <label for="foto_ktp" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition">
                                Choose File
                            </label>
                            <div wire:loading wire:target="foto_ktp" class="text-[11px] text-blue-500 mt-2">Mengunggah...</div>
                            @error('foto_ktp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Upload Foto Gudang --}}
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center hover:border-orange-300 transition-colors">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Upload Foto Gudang / Usaha *</label>
                            @if($foto_usaha)
                                <div class="mb-3">
                                    <img src="{{ $foto_usaha->temporaryUrl() }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm">
                                    <span class="text-[11px] text-green-600 font-bold mt-1 block">✓ Tersimpan</span>
                                </div>
                            @else
                                <div class="mb-3 text-gray-300">
                                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                            <input type="file" wire:model="foto_usaha" id="foto_usaha" class="hidden" accept="image/*">
                            <label for="foto_usaha" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition">
                                Choose File
                            </label>
                            <div wire:loading wire:target="foto_usaha" class="text-[11px] text-blue-500 mt-2">Mengunggah...</div>
                            @error('foto_usaha') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                    </div>

                    {{-- Submit --}}
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-green-100 transition">
                            <div wire:loading wire:target="kirimPengajuan">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                            Kirim Pengajuan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif

</div>