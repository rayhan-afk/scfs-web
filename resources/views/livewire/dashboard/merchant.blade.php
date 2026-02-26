<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\MerchantProfile;

new 
#[Layout('layouts.app')] 
class extends Component {
    use WithFileUploads;

    public $profile;
    public $status_verifikasi;

    // Form Variables (Ditambah NIK & Info Rekening)
    public $nama_kantin, $nama_pemilik, $nik, $no_hp, $lokasi_blok, $info_pencairan;
    public $foto_ktp, $foto_kantin;

    public function mount()
    {
        $user = Auth::user();
        
        $this->profile = MerchantProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status_verifikasi' => 'belum_melengkapi',
                'nama_pemilik' => $user->name,
                'nama_kantin' => '', 
                'nik' => '',             // Default kosong
                'no_hp' => '',       
                'lokasi_blok' => '', 
                'info_pencairan' => '',  // Default kosong
                'persentase_bagi_hasil' => 0, 
                'saldo_token' => 0,
                'tagihan_setoran_tunai' => 0,
            ]
        );

        $this->status_verifikasi = $this->profile->status_verifikasi;

        // Isi form dengan data yang sudah ada
        $this->nama_kantin = $this->profile->nama_kantin;
        $this->nama_pemilik = $this->profile->nama_pemilik;
        $this->nik = $this->profile->nik;
        $this->no_hp = $this->profile->no_hp;
        $this->lokasi_blok = $this->profile->lokasi_blok;
        $this->info_pencairan = $this->profile->info_pencairan;
    }

    public function submitOnboarding()
    {
        $this->validate([
            'nama_kantin' => 'required|string|max:255',
            'nama_pemilik' => 'required|string|max:255',
            'nik' => 'required|numeric|digits_between:15,17', // Validasi angka KTP
            'no_hp' => 'required|string|max:20',
            'lokasi_blok' => 'required|string|max:255',
            'info_pencairan' => 'required|string|max:255', // Validasi Rekening
            'foto_ktp' => 'nullable|image|max:2048', 
            'foto_kantin' => 'nullable|image|max:2048', 
        ]);

        $updateData = [
            'nama_kantin' => $this->nama_kantin,
            'nama_pemilik' => $this->nama_pemilik,
            'nik' => $this->nik,
            'no_hp' => $this->no_hp,
            'lokasi_blok' => $this->lokasi_blok,
            'info_pencairan' => $this->info_pencairan,
            'status_verifikasi' => 'menunggu_review', 
        ];

        if ($this->foto_ktp && !is_string($this->foto_ktp)) {
            $updateData['foto_ktp'] = $this->foto_ktp->store('merchants/ktp', 'public');
        }
        if ($this->foto_kantin && !is_string($this->foto_kantin)) {
            $updateData['foto_kantin'] = $this->foto_kantin->store('merchants/kantin', 'public');
        }

        $this->profile->update($updateData);
        $this->status_verifikasi = 'menunggu_review';
        
        session()->flash('message', 'Data berhasil dikirim! Silakan tunggu verifikasi dari pihak LKBB.');
    }

    public function perbaikiData()
    {
        $this->status_verifikasi = 'belum_melengkapi';
        $this->profile->update(['status_verifikasi' => 'belum_melengkapi']);
    }

    // Dummy Data
    public function getRiwayatHariIniProperty()
    {
        return [
            ['id' => 'INV-001', 'waktu' => '12:30', 'pembeli' => 'Ahmad (MHS)', 'nominal' => 15000, 'metode' => 'SCFS Pay'],
            ['id' => 'INV-002', 'waktu' => '13:00', 'pembeli' => 'Siti (MHS)', 'nominal' => 12000, 'metode' => 'SCFS Pay'],
        ];
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full max-w-7xl mx-auto space-y-6 relative">

    @if($status_verifikasi === 'belum_melengkapi')
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">📝</div>
                <h2 class="text-2xl font-bold text-gray-900">Selamat Datang di SCFS!</h2>
                <p class="text-gray-500 mt-2">Sebelum mulai berjualan dan menerima pembayaran mahasiswa, mohon lengkapi profil usaha dan dokumen Anda terlebih dahulu.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800 text-sm">Form Pendaftaran Mitra Kantin</h3>
                </div>
                <div class="p-6 space-y-5">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Kantin / Usaha</label>
                            <input wire:model="nama_kantin" type="text" placeholder="Contoh: Ayam Geprek Bu Ani" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2.5">
                            @error('nama_kantin') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Lokasi / Blok Kantin</label>
                            <input wire:model="lokasi_blok" type="text" placeholder="Contoh: Kantin Timur, Blok B" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2.5">
                            @error('lokasi_blok') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Pemilik (Sesuai KTP)</label>
                            <input wire:model="nama_pemilik" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2.5">
                            @error('nama_pemilik') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nomor Induk Kependudukan (NIK)</label>
                            <input wire:model="nik" type="text" placeholder="16 Digit Angka NIK" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2.5">
                            @error('nik') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA Aktif</label>
                            <input wire:model="no_hp" type="text" placeholder="0812..." class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2.5">
                            @error('no_hp') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1.5">Info Rekening / E-Wallet (Pencairan)</label>
                            <input wire:model="info_pencairan" type="text" placeholder="Cth: GoPay 0812xxx a/n Budi" class="w-full text-sm rounded-xl border-blue-200 focus:border-blue-500 focus:ring-blue-500 py-2.5 bg-blue-50/30">
                            @error('info_pencairan') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <hr class="border-gray-100 my-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Upload Foto KTP</label>
                            <input wire:model="foto_ktp" type="file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            @error('foto_ktp') <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span> @enderror
                            @if($foto_ktp && !is_string($foto_ktp))
                                <img src="{{ $foto_ktp->temporaryUrl() }}" class="mt-3 mx-auto h-20 rounded-lg object-cover shadow-sm">
                            @endif
                        </div>
                        
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Upload Foto Depan Kantin</label>
                            <input wire:model="foto_kantin" type="file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            @error('foto_kantin') <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span> @enderror
                            @if($foto_kantin && !is_string($foto_kantin))
                                <img src="{{ $foto_kantin->temporaryUrl() }}" class="mt-3 mx-auto h-20 rounded-lg object-cover shadow-sm">
                            @endif
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-right">
                    <button wire:click="submitOnboarding" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl shadow-sm hover:bg-blue-700 transition">
                        Kirim Pengajuan
                    </button>
                </div>
            </div>
        </div>

    @elseif($status_verifikasi === 'menunggu_review')
        <div class="max-w-xl mx-auto mt-10">
            <div class="bg-white p-8 rounded-3xl shadow-xl text-center border border-gray-100">
                <div class="w-20 h-20 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl animate-pulse">⏳</div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Data Sedang Ditinjau</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">Terima kasih telah melengkapi data! Tim LKBB saat ini sedang melakukan proses verifikasi terhadap dokumen kantin Anda. Proses ini biasanya memakan waktu 1x24 Jam.</p>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-50 text-yellow-700 rounded-full text-xs font-bold border border-yellow-200 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 animate-ping"></span> Status: Pending
                </div>
            </div>
        </div>

    @elseif($status_verifikasi === 'ditolak')
        <div class="max-w-xl mx-auto mt-10">
            <div class="bg-white p-8 rounded-3xl shadow-xl text-center border border-red-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-red-500"></div>
                <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">❌</div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Pengajuan Ditolak</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">Mohon maaf, pengajuan pendaftaran merchant Anda belum dapat kami setujui karena alasan berikut:</p>
                
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm font-medium mb-6 text-left">
                    "{{ $profile->catatan_penolakan ?? 'Dokumen tidak lengkap atau foto tidak jelas.' }}"
                </div>

                <button wire:click="perbaikiData" class="px-6 py-2.5 bg-gray-900 text-white font-bold text-sm rounded-xl shadow-sm hover:bg-gray-800 transition flex items-center justify-center gap-2 mx-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Perbaiki & Kirim Ulang
                </button>
            </div>
        </div>

    @elseif($status_verifikasi === 'disetujui')
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Halo, {{ $profile->nama_kantin }}! 👋</h2>
                <p class="text-gray-500 text-sm mt-1">Siap melayani mahasiswa hari ini? Jangan lupa cek saldo secara berkala.</p>
            </div>
            <button class="px-5 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Scan QR Mahasiswa
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
                <div class="relative z-10">
                    <p class="text-emerald-100 text-[10px] font-bold tracking-wider mb-1">SALDO TOKEN (HAK KANTIN)</p>
                    <h3 class="text-3xl font-extrabold tracking-tight">Rp {{ number_format($profile->saldo_token ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <p class="text-[10px] text-gray-400 font-bold tracking-wider mb-1 uppercase">Penjualan Hari Ini</p>
                <h3 class="text-2xl font-bold text-gray-900">Rp 27.000</h3>
                <p class="text-xs text-emerald-600 font-medium mt-1">↑ 2 Pesanan baru</p>
            </div>

            <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
                <div class="relative z-10">
                    <p class="text-rose-100 text-[10px] font-bold tracking-wider mb-1 uppercase">Hutang Bagi Hasil LKBB ({{ $profile->persentase_bagi_hasil }}%)</p>
                    <h3 class="text-3xl font-extrabold tracking-tight">Rp {{ number_format($profile->tagihan_setoran_tunai ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 text-sm">Riwayat Penjualan Terakhir</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Waktu</th>
                            <th class="px-6 py-4">ID Pesanan</th>
                            <th class="px-6 py-4">Pembeli</th>
                            <th class="px-6 py-4 text-center">Metode</th>
                            <th class="px-6 py-4 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($this->riwayatHariIni as $trx)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $trx['waktu'] }}</td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-gray-900">{{ $trx['id'] }}</td>
                            <td class="px-6 py-4 font-medium text-sm text-gray-700">{{ $trx['pembeli'] }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-blue-50 text-blue-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase">{{ $trx['metode'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-emerald-600">Rp {{ number_format($trx['nominal'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>