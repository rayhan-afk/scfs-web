<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public User $user;
    public $activeTab = 'riwayat_po'; 

    // Variabel Form Edit Pemasok
    public $isEditModalOpen = false;
    public $edit_nama_perusahaan, $edit_nama_pic, $edit_no_hp, $edit_kategori_barang, $edit_alamat, $edit_info_bank, $edit_status_kemitraan;
    public $edit_email;

    public function mount($id)
    {
        $this->user = User::with('pemasokProfile')->findOrFail($id);
    }

    // Dummy Data Riwayat PO (Purchase Order)
    public function getDummyPoProperty()
    {
        return [
            ['id' => 'PO-260224-001', 'tanggal' => '24 Feb 2026', 'kantin_tujuan' => 'Ayam Geprek Bu Ani', 'item' => 'Beras 50kg, Minyak 10L', 'nominal' => 850000, 'status' => 'Selesai'],
            ['id' => 'PO-250224-012', 'tanggal' => '23 Feb 2026', 'kantin_tujuan' => 'Kopi Pojok Teknik', 'item' => 'Gula 5kg, Susu Kental Manis 1 Dus', 'nominal' => 250000, 'status' => 'Selesai'],
            ['id' => 'PO-220224-045', 'tanggal' => '22 Feb 2026', 'kantin_tujuan' => 'Kantin Timur Blok A', 'item' => 'Ayam Potong 20 Ekor', 'nominal' => 700000, 'status' => 'Selesai'],
        ];
    }

    // =====================================
    // FUNGSI EDIT DATA PEMASOK
    // =====================================
    public function openEditModal()
    {
        if ($this->user->pemasokProfile) {
            $this->edit_nama_perusahaan = $this->user->pemasokProfile->nama_perusahaan;
            $this->edit_nama_pic = $this->user->pemasokProfile->nama_pic;
            $this->edit_no_hp = $this->user->pemasokProfile->no_hp;
            $this->edit_kategori_barang = $this->user->pemasokProfile->kategori_barang;
            $this->edit_alamat = $this->user->pemasokProfile->alamat;
            $this->edit_info_bank = $this->user->pemasokProfile->info_bank;
            $this->edit_status_kemitraan = $this->user->pemasokProfile->status_kemitraan;
            
            $this->edit_email = $this->user->email;
            
            $this->isEditModalOpen = true;
        }
    }

    public function closeEditModal()
    {
        $this->isEditModalOpen = false;
    }

    public function updatePemasok()
    {
        $this->validate([
            'edit_nama_perusahaan' => 'required|string|max:255',
            'edit_nama_pic' => 'required|string|max:255',
            'edit_email' => 'required|email|unique:users,email,' . $this->user->id,
            'edit_kategori_barang' => 'required|string',
            'edit_status_kemitraan' => 'required|in:aktif,nonaktif',
        ]);

        $this->user->update([
            'name' => $this->edit_nama_pic, 
            'email' => $this->edit_email,
        ]);

        if ($this->user->pemasokProfile) {
            $this->user->pemasokProfile->update([
                'nama_perusahaan' => $this->edit_nama_perusahaan,
                'nama_pic' => $this->edit_nama_pic,
                'no_hp' => $this->edit_no_hp,
                'kategori_barang' => $this->edit_kategori_barang,
                'alamat' => $this->edit_alamat,
                'info_bank' => $this->edit_info_bank,
                'status_kemitraan' => $this->edit_status_kemitraan,
            ]);
        }

        $this->user->refresh();
        $this->closeEditModal();
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('admin.pemasok.index') }}" class="hover:text-indigo-600 transition">Data Pemasok</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="font-medium text-gray-900">Detail & Keuangan Pemasok</span>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-16 h-16 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-3xl shadow-inner border border-indigo-200">
                📦
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $user->pemasokProfile->nama_perusahaan ?? 'Nama Perusahaan' }}</h2>
                    @if(($user->pemasokProfile->status_kemitraan ?? 'nonaktif') == 'aktif')
                        <span class="bg-indigo-100 text-indigo-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-indigo-200 uppercase tracking-wide">Mitra Aktif</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">Nonaktif</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">PIC: <span class="font-medium text-gray-700">{{ $user->pemasokProfile->nama_pic ?? '-' }}</span></p>
            </div>
        </div>
        
        <div class="flex gap-2 w-full md:w-auto">
            <a href="{{ route('admin.pemasok.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 font-medium text-sm transition text-center w-full md:w-auto flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
            <button wire:click="openEditModal" class="bg-[#137FEC] text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-[#0f6fd1] transition shadow-lg shadow-gray-200 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Edit Data
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
        
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full relative overflow-hidden">
            <div class="flex items-center gap-2 mb-6 text-blue-500 font-bold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Informasi & Kontak Pemasok
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1">
                <div class="space-y-5">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <div class="min-w-0">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email Login</p>
                            <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No Handphone (PIC)</p>
                            <p class="text-gray-900 font-medium text-sm">{{ $user->pemasokProfile->no_hp ?: '-' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Kategori Barang</p>
                            <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-0.5 rounded-md font-medium border border-gray-200 mt-1 inline-block">
                                {{ $user->pemasokProfile->kategori_barang ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Alamat Gudang/Toko</p>
                            <p class="text-gray-900 font-medium text-sm leading-relaxed">{{ $user->pemasokProfile->alamat ?: '-' }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-3 bg-indigo-50 border border-indigo-100 rounded-xl mt-2">
                        <svg class="w-5 h-5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                        <div>
                            <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider mb-0.5">Info Rekening Bank (Tujuan Pembayaran)</p>
                            <p class="text-indigo-900 font-bold text-sm">{{ $user->pemasokProfile->info_bank ?: 'Belum diisi' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full justify-center">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            
            <div class="relative z-10 flex-1 flex flex-col justify-center">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-orange-50 text-[10px] font-bold tracking-wider uppercase">HUTANG LKBB</span>
                    </div>
                </div>
                
                <div>
                    <p class="text-orange-100 text-xs font-bold tracking-wider mb-1">TOTAL TAGIHAN BERJALAN</p>
                    <h3 class="text-3xl lg:text-4xl font-extrabold tracking-tight drop-shadow-md truncate">
                        <span class="text-xl align-top mr-1 opacity-80">Rp</span>{{ number_format($user->pemasokProfile->tagihan_berjalan ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] text-orange-100 mt-3 opacity-90 leading-relaxed">
                        Total tagihan atas suplai barang (PO) ke kantin yang sedang menunggu pelunasan transfer dari pihak LKBB ke rekening pemasok.
                    </p>
                </div>
            </div>
        </div>
        
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4">
        
        <div class="flex border-b border-gray-100 px-6 gap-6 overflow-x-auto">
            <button wire:click="$set('activeTab', 'riwayat_po')" 
                class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'riwayat_po' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                Riwayat Pengiriman (PO) Kantin
            </button>
            <button wire:click="$set('activeTab', 'pembayaran')" 
                class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'pembayaran' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                Histori Pembayaran LKBB
            </button>
        </div>
        
        <div class="p-0 overflow-x-auto">
            
            @if($activeTab == 'riwayat_po')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Pesanan (PO)</th>
                        <th class="px-6 py-4">Tanggal Kirim</th>
                        <th class="px-6 py-4">Kantin Tujuan</th>
                        <th class="px-6 py-4">Rincian Barang</th>
                        <th class="px-6 py-4 text-right">Total Tagihan</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->dummyPo as $po)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-500">{{ $po['id'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $po['tanggal'] }}</td>
                        <td class="px-6 py-4 font-bold text-sm text-gray-900">{{ $po['kantin_tujuan'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 truncate max-w-xs" title="{{ $po['item'] }}">{{ $po['item'] }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">Rp {{ number_format($po['nominal'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-emerald-100 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Selesai</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat PO dari pemasok ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            @if($activeTab == 'pembayaran')
            <div class="px-6 py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <p class="text-gray-500 text-sm font-medium">Belum ada riwayat pelunasan tagihan ke rekening pemasok oleh LKBB.</p>
            </div>
            @endif
            
        </div>
    </div>

    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Data Pemasok
                </h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Perusahaan / Toko</label>
                        <input wire:model="edit_nama_perusahaan" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Kategori Produk</label>
                        <select wire:model="edit_kategori_barang" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5 cursor-pointer">
                            <option value="Sembako">Sembako</option>
                            <option value="Daging & Unggas">Daging & Unggas</option>
                            <option value="Sayur & Buah">Sayur & Buah</option>
                            <option value="Kemasan">Kemasan</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama PIC (Penanggung Jawab)</label>
                        <input wire:model="edit_nama_pic" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA</label>
                        <input wire:model="edit_no_hp" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Aktif</label>
                        <input wire:model="edit_email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Status Kemitraan</label>
                        <select wire:model="edit_status_kemitraan" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5 cursor-pointer">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Non-aktif (Blacklist)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Lengkap</label>
                    <textarea wire:model="edit_alamat" rows="2" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white"></textarea>
                </div>
                
                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-xl">
                    <label class="block text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1.5">Info Rekening Bank (Tujuan Pembayaran)</label>
                    <input wire:model="edit_info_bank" type="text" placeholder="Cth: BCA 1234567890 a.n PT Pangan" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="updatePemasok" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors shadow-sm focus:ring-4 focus:ring-indigo-100">Simpan Perubahan</button>
            </div>
        </div>
    </div>
    @endif

</div>