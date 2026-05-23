<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\PemasokProfile;
use App\Notifications\NewSupplierSubmission;

new #[Layout('layouts.app')]
class extends Component {
    use WithFileUploads;

    public $profile;
    public $status;

    // Form fields (selaras schema flat baru)
    public $nama_perusahaan;
    public $nama_pic;
    public $nik;
    public $no_hp;
    public $alamat;

    // Rekening flat
    public $nama_bank = 'BCA';
    public $bank_lainnya = '';
    public $no_rekening = '';
    public $atas_nama_rekening = '';

    // Dokumen
    public $foto_ktp;
    public $foto_gudang;

    public array $daftarBank = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BJB', 'GoPay', 'OVO', 'Lainnya'];

    public function mount()
    {
        $user = Auth::user();
        $this->profile = PemasokProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['kategori_barang' => 'Lainnya']
        );
        $this->status = $this->profile->status_verifikasi;

        $this->nama_perusahaan    = $this->profile->nama_perusahaan;
        $this->nama_pic           = $this->profile->nama_pic ?? $user->name;
        $this->nik                = $this->profile->nik;
        $this->no_hp              = $this->profile->no_hp;
        $this->alamat             = $this->profile->alamat;
        $this->atas_nama_rekening = $this->profile->atas_nama_rekening;

        $standardBanks = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BJB', 'GoPay', 'OVO'];
        if (in_array($this->profile->nama_bank, $standardBanks)) {
            $this->nama_bank = $this->profile->nama_bank;
        } elseif (!empty($this->profile->nama_bank)) {
            $this->nama_bank = 'Lainnya';
            $this->bank_lainnya = $this->profile->nama_bank;
        }
        $this->no_rekening = $this->profile->no_rekening;
    }

    public function kirimPengajuan()
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
            'foto_ktp'           => $this->profile->foto_ktp ? 'nullable|image|max:2048' : 'required|image|max:2048',
            'foto_gudang'        => $this->profile->foto_gudang ? 'nullable|image|max:2048' : 'required|image|max:2048',
        ], [
            'nik.digits' => 'NIK harus 16 digit.',
            'no_rekening.numeric' => 'Nomor rekening hanya boleh berisi angka.',
            'bank_lainnya.required_if' => 'Wajib isi nama bank jika memilih "Lainnya".',
        ]);

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
            'status_verifikasi'  => 'menunggu_review',
        ];

        if ($this->foto_ktp && !is_string($this->foto_ktp)) {
            $updateData['foto_ktp'] = $this->foto_ktp->store('pemasok/ktp', 'public');
        }
        if ($this->foto_gudang && !is_string($this->foto_gudang)) {
            $updateData['foto_gudang'] = $this->foto_gudang->store('pemasok/gudang', 'public');
        }

        $this->profile->update($updateData);
        $this->profile->refresh();
        $this->status = 'menunggu_review';

        foreach (\App\Models\User::where('role', 'lkbb')->get() as $lkbb) {
            $lkbb->notify(new NewSupplierSubmission($this->profile));
        }

        session()->flash('message', 'Pengajuan berhasil dikirim! Tim LKBB akan memverifikasi data Anda.');
    }

    public function ajukanUlang()
    {
        // Tombol "Revisi" pada fase ditolak — buka kembali form
        $this->profile->update(['status_verifikasi' => 'belum_melengkapi']);
        $this->profile->refresh();
        $this->status = 'belum_melengkapi';
    }
}; ?>

<div class="max-w-5xl mx-auto py-8 px-4">

    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-2xl flex items-center gap-3">
            <span class="text-xl">✅</span>
            <span class="font-medium text-sm">{{ session('message') }}</span>
        </div>
    @endif

    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-800">Status Pengajuan Pemasok</h1>
        <p class="text-gray-500 mt-1">Pantau proses verifikasi pemasok SCFS Anda secara realtime.</p>
    </div>

    {{-- ===================== DISETUJUI ===================== --}}
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
                        <div class="flex items-center justify-between"><span>Foto KTP</span><span>{{ $profile?->foto_ktp ? '✅' : '❌' }}</span></div>
                        <div class="flex items-center justify-between"><span>Foto Gudang/Usaha</span><span>{{ $profile?->foto_gudang ? '✅' : '❌' }}</span></div>
                        <div class="flex items-center justify-between"><span>NIK</span><span>{{ $profile?->nik ? '✅' : '❌' }}</span></div>
                        <div class="flex items-center justify-between"><span>No HP</span><span>{{ $profile?->no_hp ? '✅' : '❌' }}</span></div>
                        <div class="flex items-center justify-between"><span>Rekening</span><span>{{ ($profile?->nama_bank && $profile?->no_rekening) ? '✅' : '❌' }}</span></div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4">Informasi Usaha</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between"><span>Nama Perusahaan</span><span class="font-semibold">{{ $profile?->nama_perusahaan ?? '-' }}</span></div>
                        <div class="flex items-center justify-between"><span>PIC</span><span class="font-semibold">{{ $profile?->nama_pic ?? '-' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span>Alamat</span><span class="font-semibold text-right max-w-[60%] line-clamp-2">{{ $profile?->alamat ?? '-' }}</span></div>
                        <div class="flex items-center justify-between"><span>Rekening</span><span class="font-semibold">{{ $profile?->nama_bank }} • {{ $profile?->no_rekening }}</span></div>
                    </div>
                </div>
            </div>
            <div class="mt-6 text-right">
                <a href="{{ route('pemasok.dashboard') }}" wire:navigate class="inline-flex items-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl transition shadow-lg shadow-emerald-100">
                    Buka Dashboard →
                </a>
            </div>
        </div>

    {{-- ===================== MENUNGGU REVIEW ===================== --}}
    @elseif($status === 'menunggu_review')
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-yellow-100 flex items-center justify-center text-3xl">⏳</div>
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Menunggu Verifikasi</h2>
                    <p class="text-gray-500 mt-1">Tim LKBB sedang memverifikasi data usaha Anda. Harap tunggu maksimal 1×24 jam.</p>
                </div>
            </div>
        </div>

    {{-- ===================== DITOLAK ===================== --}}
    @elseif($status === 'ditolak')
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 mb-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-red-100 flex items-center justify-center text-3xl">❌</div>
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Pengajuan Ditolak</h2>
                    <p class="text-gray-500 mt-1">Silakan perbaiki data lalu kirim ulang pengajuan Anda.</p>
                </div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5 mb-6">
                <h3 class="font-bold text-red-700 mb-2">Alasan Penolakan</h3>
                <p class="text-sm text-red-600 leading-relaxed">{{ $profile->catatan_penolakan ?? 'Tidak ada catatan.' }}</p>
            </div>
            <button wire:click="ajukanUlang" class="px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-2xl transition shadow-lg">
                Perbaiki & Kirim Ulang
            </button>
        </div>

    {{-- ===================== FORM PENGAJUAN (belum_melengkapi) ===================== --}}
    @else
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-800 mb-1">Selamat Datang di Mitra Pemasok SCFS!</h2>
                <p class="text-gray-500 mb-8">Sebelum mulai menyuplai barang ke kantin, mohon lengkapi profil usaha Anda.</p>

                <form wire:submit.prevent="kirimPengajuan" class="space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nama Perusahaan / Usaha</label>
                            <input type="text" wire:model="nama_perusahaan" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm" placeholder="Cth: PT Sumber Pangan">
                            @error('nama_perusahaan') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Alamat Gudang / Tempat Usaha</label>
                            <input type="text" wire:model="alamat" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm" placeholder="Jl. Contoh No. 1, Kota">
                            @error('alamat') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nama PIC (Sesuai KTP)</label>
                            <input type="text" wire:model="nama_pic" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm">
                            @error('nama_pic') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nomor Induk Kependudukan (NIK)</label>
                            <input type="text" wire:model.defer="nik" maxlength="16" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'').slice(0,16)" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm font-mono" placeholder="16 digit NIK">
                            @error('nik') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">No Handphone / WA Aktif</label>
                            <input type="text" wire:model="no_hp" maxlength="20" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm" placeholder="08xxxxxxxxxx">
                            @error('no_hp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div></div>
                    </div>

                    {{-- REKENING FLAT (satu kartu, tanpa duplikasi) --}}
                    <div class="bg-orange-50/40 border border-orange-100 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            <h4 class="font-bold text-orange-900 text-sm">Rekening Penerimaan Dana</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-orange-700 uppercase tracking-widest mb-1.5">Bank</label>
                                <select wire:model.live="nama_bank" class="w-full rounded-xl border-orange-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm bg-white">
                                    @foreach($daftarBank as $bank)
                                        <option value="{{ $bank }}">{{ $bank === 'Lainnya' ? 'Bank Lainnya...' : $bank }}</option>
                                    @endforeach
                                </select>
                                @if($nama_bank === 'Lainnya')
                                    <input type="text" wire:model="bank_lainnya" maxlength="50" placeholder="Nama bank..." class="mt-2 w-full rounded-xl border-orange-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm bg-white">
                                    @error('bank_lainnya') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                @endif
                                @error('nama_bank') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-orange-700 uppercase tracking-widest mb-1.5">Nomor Rekening</label>
                                <input type="text" wire:model="no_rekening" maxlength="20" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'').slice(0,20)" placeholder="1234567890" class="w-full rounded-xl border-orange-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm bg-white font-mono">
                                @error('no_rekening') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-orange-700 uppercase tracking-widest mb-1.5">Atas Nama (Opsional)</label>
                                <input type="text" wire:model="atas_nama_rekening" maxlength="100" placeholder="Pemilik rekening" class="w-full rounded-xl border-orange-200 shadow-sm focus:border-orange-500 focus:ring-orange-500 py-3 text-sm bg-white">
                                @error('atas_nama_rekening') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center hover:border-orange-300 transition-colors">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Upload Foto KTP *</label>
                            @if($foto_ktp)
                                <div class="mb-3">
                                    <img src="{{ $foto_ktp->temporaryUrl() }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm">
                                    <span class="text-[11px] text-green-600 font-bold mt-1 block">✓ Siap diunggah</span>
                                </div>
                            @elseif($profile?->foto_ktp)
                                <img src="{{ asset('storage/' . $profile->foto_ktp) }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm mb-2">
                                <span class="text-[11px] text-blue-500 block">Foto KTP saat ini</span>
                            @else
                                <div class="mb-3 text-gray-300">
                                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                            @endif
                            <input type="file" wire:model="foto_ktp" id="foto_ktp" class="hidden" accept="image/*">
                            <label for="foto_ktp" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition mt-2">Choose File</label>
                            <div wire:loading wire:target="foto_ktp" class="text-[11px] text-blue-500 mt-2">Mengunggah...</div>
                            @error('foto_ktp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center hover:border-orange-300 transition-colors">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Upload Foto Gudang / Usaha *</label>
                            @if($foto_gudang)
                                <div class="mb-3">
                                    <img src="{{ $foto_gudang->temporaryUrl() }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm">
                                    <span class="text-[11px] text-green-600 font-bold mt-1 block">✓ Siap diunggah</span>
                                </div>
                            @elseif($profile?->foto_gudang)
                                <img src="{{ asset('storage/' . $profile->foto_gudang) }}" class="h-28 mx-auto rounded-lg object-cover shadow-sm mb-2">
                                <span class="text-[11px] text-blue-500 block">Foto gudang saat ini</span>
                            @else
                                <div class="mb-3 text-gray-300">
                                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                            @endif
                            <input type="file" wire:model="foto_gudang" id="foto_gudang" class="hidden" accept="image/*">
                            <label for="foto_gudang" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition mt-2">Choose File</label>
                            <div wire:loading wire:target="foto_gudang" class="text-[11px] text-blue-500 mt-2">Mengunggah...</div>
                            @error('foto_gudang') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-emerald-100 transition">
                            <div wire:loading wire:target="kirimPengajuan">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </div>
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
