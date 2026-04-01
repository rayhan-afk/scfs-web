<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public User $user;
    public $activeTab = 'riwayat'; 

    // Variabel Form Edit Donatur
    public $isEditModalOpen = false;
    public $edit_nama_lengkap, $edit_institusi, $edit_no_hp, $edit_rekening_sumber, $edit_alamat, $edit_tipe_donatur, $edit_status_kemitraan;
    public $edit_email;

    public function mount($id)
    {
        $this->user = User::with('donaturProfile')->findOrFail($id);
    }

    public function getDummyDonasiProperty()
    {
        return [
            ['id' => 'DON-260224-001', 'tanggal' => '24 Feb 2026', 'metode' => 'Transfer BCA', 'keterangan' => 'Donasi Rutin Februari', 'nominal' => 2500000, 'status' => 'Berhasil'],
            ['id' => 'DON-150126-042', 'tanggal' => '15 Jan 2026', 'metode' => 'QRIS / E-Wallet', 'keterangan' => 'Bantuan Awal Semester', 'nominal' => 5000000, 'status' => 'Berhasil'],
        ];
    }

    public function openEditModal()
    {
        if ($this->user->donaturProfile) {
            $this->edit_nama_lengkap = $this->user->donaturProfile->nama_lengkap;
            $this->edit_institusi = $this->user->donaturProfile->institusi;
            $this->edit_no_hp = $this->user->donaturProfile->no_hp;
            $this->edit_rekening_sumber = $this->user->donaturProfile->rekening_sumber;
            $this->edit_alamat = $this->user->donaturProfile->alamat;
            $this->edit_tipe_donatur = $this->user->donaturProfile->tipe_donatur;
            $this->edit_status_kemitraan = $this->user->donaturProfile->status_kemitraan;
            
            $this->edit_email = $this->user->email;
            
            $this->isEditModalOpen = true;
        }
    }

    public function closeEditModal()
    {
        $this->isEditModalOpen = false;
    }

    public function updateDonatur()
    {
        $this->validate([
            'edit_nama_lengkap' => 'required|string|max:255',
            'edit_email' => 'required|email|unique:users,email,' . $this->user->id,
            'edit_status_kemitraan' => 'required|in:aktif,nonaktif',
        ]);

        $this->user->update([
            'name' => $this->edit_nama_lengkap, 
            'email' => $this->edit_email,
        ]);

        if ($this->user->donaturProfile) {
            $this->user->donaturProfile->update([
                'nama_lengkap' => $this->edit_nama_lengkap,
                'institusi' => $this->edit_institusi,
                'no_hp' => $this->edit_no_hp,
                'rekening_sumber' => $this->edit_rekening_sumber,
                'alamat' => $this->edit_alamat,
                'tipe_donatur' => $this->edit_tipe_donatur,
                'status_kemitraan' => $this->edit_status_kemitraan,
            ]);
        }

        $this->user->refresh();
        $this->closeEditModal();
    }

    public function simulasiTambahDonasi()
    {
        if ($this->user->donaturProfile) {
            $this->user->donaturProfile->update([
                'total_donasi' => $this->user->donaturProfile->total_donasi + 1000000
            ]);
            $this->user->refresh();
        }
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('admin.donatur.index') }}" class="hover:text-rose-600 transition">Data Donatur</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="font-medium text-gray-900">Detail Pahlawan Donasi</span>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-2xl font-bold shadow-inner border border-rose-200">
                {{ strtoupper(substr($user->donaturProfile->nama_lengkap ?? $user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $user->donaturProfile->nama_lengkap ?? 'Nama Donatur' }}</h2>
                    @if(($user->donaturProfile->status_kemitraan ?? 'nonaktif') == 'aktif')
                        <span class="bg-rose-100 text-rose-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-rose-200 uppercase tracking-wide">Donatur Aktif</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">Nonaktif</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm text-gray-500">Afiliasi: <span class="font-medium text-gray-700">{{ $user->donaturProfile->institusi ?: 'Hamba Allah / Individu' }}</span></p>
                    <span class="text-gray-300">•</span>
                    @if(($user->donaturProfile->tipe_donatur ?? 'insidental') == 'rutin')
                        <span class="text-blue-600 font-bold text-xs">Rutin</span>
                    @else
                        <span class="text-gray-500 font-bold text-xs">Insidental</span>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="flex gap-2 w-full md:w-auto">
            <a href="{{ route('admin.donatur.index') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 font-medium text-sm transition text-center w-full md:w-auto flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
            <button wire:click="openEditModal" class="px-5 py-2.5 bg-rose-600 text-white rounded-xl hover:bg-rose-700 font-medium text-sm shadow-sm transition flex items-center justify-center w-full md:w-auto gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Edit Data
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full relative overflow-hidden lg:col-span-1">
            <div class="flex items-center gap-2 mb-6 text-rose-700 font-bold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                Informasi Kontak
            </div>
            
            <div class="space-y-4 flex-1">
                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email / Akun Login</p>
                        <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No Handphone</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->donaturProfile->no_hp ?: '-' }}</p>
                    </div>
                </div>
                
                <hr class="border-gray-100 my-2">

                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Alamat Pengiriman Laporan</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->donaturProfile->alamat ?: '-' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Rekening Sumber (Pencocokan)</p>
                        <p class="text-blue-700 font-bold text-sm bg-blue-50 px-2 py-0.5 mt-0.5 rounded inline-block">{{ $user->donaturProfile->rekening_sumber ?: 'Belum didata' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full lg:col-span-1">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                        </div>
                        <span class="text-rose-50 text-[10px] font-bold tracking-wider">KONTRIBUSI FINANSIAL</span>
                    </div>
                </div>
                
                <div>
                    <p class="text-rose-100 text-xs font-bold tracking-wider mb-1">TOTAL DONASI DIBERIKAN</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        <span class="text-xl align-top mr-1 opacity-80">Rp</span>{{ number_format($user->donaturProfile->total_donasi ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-rose-100 mt-2 opacity-90 leading-tight">Total akumulasi dana bantuan yang telah disalurkan oleh donatur ini.</p>
                </div>
            </div>
            
            <div class="relative z-10 mt-5 pt-4 border-t border-rose-400/30">
                <button wire:click="simulasiTambahDonasi" class="w-full py-2.5 bg-white text-rose-700 font-bold text-sm rounded-xl shadow-sm hover:bg-rose-50 transition-colors flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Simulasi Terima Donasi Baru (+1 Juta)
                </button>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-500 to-fuchsia-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full lg:col-span-1">
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full -ml-10 -mb-10 pointer-events-none"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <span class="text-pink-50 text-[10px] font-bold tracking-wider">SOCIAL IMPACT</span>
                    </div>
                </div>
                
                <div>
                    <p class="text-pink-100 text-xs font-bold tracking-wider mb-1">ESTIMASI MAHASISWA TERBANTU</p>
                    <h3 class="text-4xl font-extrabold tracking-tight drop-shadow-md truncate">
                        {{ floor(($user->donaturProfile->total_donasi ?? 0) / 500000) }} <span class="text-lg font-medium opacity-80 ml-1">Orang</span>
                    </h3>
                    <p class="text-[10px] text-pink-100 mt-2 opacity-90 leading-tight">Berdasarkan rasio penyaluran bantuan rata-rata Rp 500.000 per mahasiswa/bulan.</p>
                </div>
            </div>
            
            <div class="relative z-10 mt-5 pt-4 border-t border-pink-400/30">
                <div class="w-full py-2.5 bg-white/10 text-white font-bold text-sm rounded-xl text-center">
                    Terima Kasih Orang Baik! ❤️
                </div>
            </div>
        </div>
        
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4">
        
        <div class="flex border-b border-gray-100 px-6 gap-6 overflow-x-auto">
            <button wire:click="$set('activeTab', 'riwayat')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'riwayat' ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-700' }}">Histori Donasi Masuk</button>
            <button wire:click="$set('activeTab', 'alokasi')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'alokasi' ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-700' }}">Laporan Penyaluran (Alokasi)</button>
        </div>
        
        <div class="p-0 overflow-x-auto">
            
            @if($activeTab == 'riwayat')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Penerimaan</th>
                        <th class="px-6 py-4">Tanggal Masuk</th>
                        <th class="px-6 py-4">Metode Transfer</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-right">Nominal Donasi</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->dummyDonasi as $trx)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-500">{{ $trx['id'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $trx['tanggal'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $trx['metode'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $trx['keterangan'] }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-rose-600">+ Rp {{ number_format($trx['nominal'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-green-100 text-green-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Berhasil</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat donasi masuk dari akun ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            @if($activeTab == 'alokasi')
            <div class="px-6 py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <p class="text-gray-500 text-sm font-medium">Laporan rincian nama mahasiswa penerima belum tersedia (Tahap Pengembangan).</p>
            </div>
            @endif
            
        </div>
    </div>

    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Profil Donatur
                </h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                        <input wire:model="edit_nama_lengkap" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Institusi / Yayasan</label>
                        <input wire:model="edit_institusi" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA</label>
                        <input wire:model="edit_no_hp" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tipe Donatur</label>
                        <select wire:model="edit_tipe_donatur" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5 cursor-pointer">
                            <option value="insidental">Insidental</option>
                            <option value="rutin">Rutin</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Rekening Sumber (Pencocokan Mutasi)</label>
                        <input wire:model="edit_rekening_sumber" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat (Kirim Laporan/Sertifikat)</label>
                        <textarea wire:model="edit_alamat" rows="1" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white"></textarea>
                    </div>
                </div>
                
                <hr class="border-gray-100 my-2">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Akses Login</label>
                        <input wire:model="edit_email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Status Kemitraan</label>
                        <select wire:model="edit_status_kemitraan" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5 cursor-pointer">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Non-aktif</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="updateDonatur" class="px-5 py-2 text-sm font-medium text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors shadow-sm focus:ring-4 focus:ring-rose-100">Simpan Perubahan</button>
            </div>
        </div>
    </div>
    @endif

</div>