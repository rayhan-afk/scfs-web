<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public User $user;
    public $activeTab = 'penjualan'; 

    // Variabel Modal Edit
    public $isEditModalOpen = false;
    public $edit_nama_kantin, $edit_nama_pemilik, $edit_lokasi_blok, $edit_persentase_bagi_hasil, $edit_status_toko;
    public $edit_email, $edit_no_hp, $edit_info_pencairan; // Tambah edit_info_pencairan

    public function mount($id)
    {
        $this->user = User::with('merchantProfile')->findOrFail($id);
    }

    public function getDummyPenjualanProperty()
    {
        return [
            ['id' => 'INV-260224-001', 'waktu' => '24 Feb 2026, 12:30', 'pembeli' => 'Ahmad Fauzi (MHS)', 'metode' => 'SCFS Pay (Token)', 'nominal' => 15000, 'potongan' => 1500, 'bersih' => 13500],
            ['id' => 'INV-260224-002', 'waktu' => '24 Feb 2026, 13:15', 'pembeli' => 'Pembeli Umum', 'metode' => 'Tunai / Cash', 'nominal' => 20000, 'potongan' => 2000, 'bersih' => 18000],
            ['id' => 'INV-250224-045', 'waktu' => '23 Feb 2026, 09:10', 'pembeli' => 'Siti Nurhaliza (MHS)', 'metode' => 'SCFS Pay (Token)', 'nominal' => 12000, 'potongan' => 1200, 'bersih' => 10800],
            ['id' => 'INV-250224-088', 'waktu' => '23 Feb 2026, 16:40', 'pembeli' => 'Pembeli Umum', 'metode' => 'Tunai / Cash', 'nominal' => 10000, 'potongan' => 1000, 'bersih' => 9000],
        ];
    }

    // =====================================
    // FUNGSI EDIT DATA MERCHANT
    // =====================================
    public function openEditModal()
    {
        if ($this->user->merchantProfile) {
            $this->edit_nama_kantin = $this->user->merchantProfile->nama_kantin;
            $this->edit_nama_pemilik = $this->user->merchantProfile->nama_pemilik;
            $this->edit_lokasi_blok = $this->user->merchantProfile->lokasi_blok;
            $this->edit_persentase_bagi_hasil = $this->user->merchantProfile->persentase_bagi_hasil;
            $this->edit_status_toko = $this->user->merchantProfile->status_toko;
            
            $this->edit_email = $this->user->email;
            $this->edit_no_hp = $this->user->merchantProfile->no_hp ?? '';
            $this->edit_info_pencairan = $this->user->merchantProfile->info_pencairan ?? ''; // Load info pencairan
            
            $this->isEditModalOpen = true;
        }
    }

    public function closeEditModal()
    {
        $this->isEditModalOpen = false;
    }

    public function updateMerchant()
    {
        $this->validate([
            'edit_nama_kantin' => 'required|string|max:255',
            'edit_nama_pemilik' => 'required|string|max:255',
            'edit_email' => 'required|email|unique:users,email,' . $this->user->id,
            'edit_persentase_bagi_hasil' => 'required|numeric|min:0|max:100',
            'edit_status_toko' => 'required|in:buka,tutup',
        ]);

        $this->user->update([
            'name' => $this->edit_nama_pemilik,
            'email' => $this->edit_email,
        ]);

        if ($this->user->merchantProfile) {
            $this->user->merchantProfile->update([
                'nama_kantin' => $this->edit_nama_kantin,
                'nama_pemilik' => $this->edit_nama_pemilik,
                'no_hp' => $this->edit_no_hp,
                'info_pencairan' => $this->edit_info_pencairan, // Simpan info pencairan
                'lokasi_blok' => $this->edit_lokasi_blok,
                'persentase_bagi_hasil' => $this->edit_persentase_bagi_hasil,
                'status_toko' => $this->edit_status_toko,
            ]);
        }

        $this->user->refresh();
        $this->closeEditModal();
    }

    public function cairkanToken()
    {
        if ($this->user->merchantProfile && $this->user->merchantProfile->saldo_token > 0) {
            $this->user->merchantProfile->update(['saldo_token' => 0]);
            $this->user->refresh();
        }
    }

    public function terimaSetoran()
    {
        if ($this->user->merchantProfile && $this->user->merchantProfile->tagihan_setoran_tunai > 0) {
            $this->user->merchantProfile->update(['tagihan_setoran_tunai' => 0]);
            $this->user->refresh();
        }
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('admin.merchant.index') }}" class="hover:text-blue-600 transition">Data Merchant</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="font-medium text-gray-900">Detail Kantin</span>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-3xl shadow-inner border border-amber-200">
                🏪
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $user->merchantProfile->nama_kantin ?? 'Nama Kantin' }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-sm text-gray-500">Pemilik: <span class="font-medium text-gray-700">{{ $user->merchantProfile->nama_pemilik ?? '-' }}</span></span>
                    <span class="text-gray-300">•</span>
                    @if(($user->merchantProfile->status_toko ?? 'tutup') == 'buka')
                        <span class="text-emerald-600 font-bold text-xs flex items-center gap-1 uppercase tracking-wide"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Buka</span>
                    @else
                        <span class="text-gray-400 font-bold text-xs uppercase tracking-wide">Tutup</span>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="flex gap-2 w-full md:w-auto">
            <a href="{{ route('admin.merchant.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium text-sm transition text-center w-full md:w-auto flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
            <button wire:click="openEditModal" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium text-sm shadow-sm transition flex items-center justify-center w-full md:w-auto gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Edit Data
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full relative overflow-hidden">
            <div class="flex items-center gap-2 mb-6 text-blue-700 font-bold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Informasi & Kontak Kantin
            </div>
            
            <div class="space-y-4 flex-1">
                <div class="grid grid-cols-2 gap-2">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <div class="min-w-0">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email Login</p>
                            <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No Handphone</p>
                            <p class="text-gray-900 font-medium text-sm">{{ $user->merchantProfile->no_hp ?: '-' }}</p>
                        </div>
                    </div>
                </div>
                
                <hr class="border-gray-100 my-2">

                <div class="grid grid-cols-2 gap-2">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Lokasi / Blok</p>
                            <p class="text-gray-900 font-medium text-sm">{{ $user->merchantProfile->lokasi_blok ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" /></svg>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Bagi Hasil (LKBB)</p>
                            <p class="text-blue-600 font-bold text-sm bg-blue-50 px-2 py-0.5 rounded-md inline-block">{{ $user->merchantProfile->persentase_bagi_hasil ?? 0 }}%</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-100 rounded-xl mt-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wider mb-0.5">Tujuan Pencairan (Bank/E-Wallet)</p>
                        <p class="text-blue-900 font-bold text-sm">{{ $user->merchantProfile->info_pencairan ?: 'Belum diisi' }}</p>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-emerald-50 text-[10px] font-bold tracking-wider">HAK KANTIN</span>
                    </div>
                </div>
                
                <div>
                    <p class="text-emerald-100 text-xs font-bold tracking-wider mb-1">SALDO TOKEN DIGITAL</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        <span class="text-xl align-top mr-1 opacity-80">Rp</span>{{ number_format($user->merchantProfile->saldo_token ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-emerald-100 mt-1 opacity-90 leading-tight">Uang transaksi mahasiswa yang harus LKBB transfer ke rekening ibu kantin.</p>
                </div>
            </div>
            
            <div class="relative z-10 mt-5 pt-4 border-t border-emerald-400/30">
                <button wire:click="cairkanToken" class="w-full py-2.5 bg-white text-emerald-700 font-bold text-sm rounded-xl shadow-sm hover:bg-emerald-50 transition-colors flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    Cairkan Saldo ke Kantin
                </button>
            </div>
        </div>

        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" /></svg>
                        </div>
                        <span class="text-rose-50 text-[10px] font-bold tracking-wider">HAK LKBB</span>
                    </div>
                </div>
                
                <div>
                    <p class="text-rose-100 text-xs font-bold tracking-wider mb-1">TAGIHAN SETORAN TUNAI</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        <span class="text-xl align-top mr-1 opacity-80">Rp</span>{{ number_format($user->merchantProfile->tagihan_setoran_tunai ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-rose-100 mt-1 opacity-90 leading-tight">Hutang bagi hasil ({{ $user->merchantProfile->persentase_bagi_hasil ?? 0 }}%) yang harus disetor ibu kantin ke LKBB.</p>
                </div>
            </div>
            
            <div class="relative z-10 mt-5 pt-4 border-t border-rose-400/30">
                <button wire:click="terimaSetoran" class="w-full py-2.5 bg-white text-rose-700 font-bold text-sm rounded-xl shadow-sm hover:bg-rose-50 transition-colors flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Terima Setoran Kantin
                </button>
            </div>
        </div>
        
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4">
        <div class="flex border-b border-gray-100 px-6 gap-6 overflow-x-auto">
            <button wire:click="$set('activeTab', 'penjualan')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'penjualan' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">Riwayat Penjualan (Order)</button>
            <button wire:click="$set('activeTab', 'pencairan')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'pencairan' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">Histori Pencairan / Settlement</button>
        </div>
        
        <div class="p-0 overflow-x-auto">
            @if($activeTab == 'penjualan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Pesanan</th>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">Pembeli & Metode</th>
                        <th class="px-6 py-4 text-right">Nilai Transaksi</th>
                        <th class="px-6 py-4 text-right">Potongan LKBB ({{ $user->merchantProfile->persentase_bagi_hasil ?? 0 }}%)</th>
                        <th class="px-6 py-4 text-right">Diterima Kantin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->dummyPenjualan as $trx)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-500">{{ $trx['id'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $trx['waktu'] }}</td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-sm text-gray-900">{{ $trx['pembeli'] }}</div>
                            <div class="text-[10px] mt-0.5 font-bold uppercase tracking-wider {{ $trx['metode'] == 'Tunai / Cash' ? 'text-rose-500' : 'text-emerald-500' }}">{{ $trx['metode'] }}</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-700">Rp {{ number_format($trx['nominal'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-rose-500">- Rp {{ number_format($trx['potongan'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-emerald-600">Rp {{ number_format($trx['bersih'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            @if($activeTab == 'pencairan')
            <div class="px-6 py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <p class="text-gray-500 text-sm font-medium">Belum ada riwayat pencairan dana (settlement) yang tercatat.</p>
            </div>
            @endif
        </div>
    </div>

    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    Edit Data & Profil Kantin
                </h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Kantin / Warung</label>
                        <input wire:model="edit_nama_kantin" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Pemilik</label>
                        <input wire:model="edit_nama_pemilik" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Aktif</label>
                        <input wire:model="edit_email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone</label>
                        <input wire:model="edit_no_hp" type="text" placeholder="Contoh: 0812..." class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Lokasi / Blok</label>
                    <input wire:model="edit_lokasi_blok" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                </div>

                <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl">
                    <label class="block text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1.5">Info Bank / E-Wallet (Tujuan Pencairan)</label>
                    <input wire:model="edit_info_pencairan" type="text" placeholder="Cth: GoPay 081234567890 a.n Ibu Ani" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Bagi Hasil LKBB (%)</label>
                        <input wire:model="edit_persentase_bagi_hasil" type="number" min="0" max="100" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Status Toko</label>
                        <select wire:model="edit_status_toko" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2 cursor-pointer">
                            <option value="buka">Buka</option>
                            <option value="tutup">Tutup</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="updateMerchant" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-sm focus:ring-4 focus:ring-blue-100">Simpan Perubahan</button>
            </div>
        </div>
    </div>
    @endif

</div>