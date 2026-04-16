<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\SupplyChain;
use App\Models\Wallet;
use App\Models\SupplierProfile;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] 
class extends Component {
    use WithFileUploads;

    public $status_verifikasi;
    public $profile;

    // Form Variables
    public $nama_usaha, $nama_pemilik, $nik, $no_hp, $alamat_gudang, $info_rekening, $catatan_penolakan;
    public $foto_ktp, $foto_usaha;

    // Dashboard Stats
    public $totalPenjualan = 0;
    public $pesananPerluDikirim = 0;
    public $stokMenipisCount = 5; // Default value atau bisa dihitung dari model produk nanti
    public $aktivitas = [];
    
    // Bank Variables
    public $nama_bank;
    public $nomor_rekening;
    public $daftar_bank = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Permata'];

    public function mount()
    {
        $user = Auth::user();
        
        $this->profile = SupplierProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['status_verifikasi' => 'belum_melengkapi', 'nama_pemilik' => $user->name]
        );

        $this->syncData();

        if ($this->status_verifikasi === 'disetujui') {
            $this->loadDashboardData();
        }
    }

    public function syncData()
    {
        $this->status_verifikasi = $this->profile->status_verifikasi;
        $this->nama_usaha = $this->profile->nama_usaha;
        $this->nama_pemilik = $this->profile->nama_pemilik;
        $this->nik = $this->profile->nik;
        $this->no_hp = $this->profile->no_hp;
        $this->alamat_gudang = $this->profile->alamat_gudang;
        $this->catatan_penolakan = $this->profile->catatan_penolakan;

        if ($this->profile->info_rekening) {
            $dataRekening = explode(' - ', $this->profile->info_rekening);
            $this->nama_bank = $dataRekening[0] ?? '';
            $this->nomor_rekening = $dataRekening[1] ?? '';
        }
    }

    public function loadDashboardData()
    {
        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->totalPenjualan = $wallet ? $wallet->balance : 0;
        
        $this->pesananPerluDikirim = SupplyChain::where('supplier_id', $user->id)
            ->where('status', 'FUNDED')->count();

        $this->aktivitas = SupplyChain::where('supplier_id', $user->id)
            ->orderBy('created_at', 'desc')->take(3)->get();
            
        // Contoh: $this->stokMenipisCount = Product::where('supplier_id', $user->id)->where('stok', '<', 10)->count();
    }

    public function submitOnboarding()
    {
        $this->validate([
            'nama_usaha' => 'required|string|max:255',
            'nik' => 'required|numeric|digits_between:15,17',
            'no_hp' => 'required|string',
            'alamat_gudang' => 'required|string',
            'nama_bank' => 'required',
            'nomor_rekening' => 'required|numeric',
            'foto_ktp' => 'nullable|image|max:2048',
            'foto_usaha' => 'nullable|image|max:2048',  
        ]);

        $updateData = [
            'nama_usaha' => $this->nama_usaha,
            'nik' => $this->nik,
            'no_hp' => $this->no_hp,
            'alamat_gudang' => $this->alamat_gudang,
            'info_rekening' => $this->nama_bank . ' - ' . $this->nomor_rekening,
            'status_verifikasi' => 'menunggu_review',
        ];

        if ($this->foto_ktp && !is_string($this->foto_ktp)) {
            $updateData['foto_ktp'] = $this->foto_ktp->store('suppliers/ktp', 'public');
        }

        if ($this->foto_usaha && !is_string($this->foto_usaha)) {
            $updateData['foto_usaha'] = $this->foto_usaha->store('suppliers/usaha', 'public');
        }

        $this->profile->update($updateData);
        $this->status_verifikasi = 'menunggu_review';
        
        session()->flash('message', 'Data berhasil dikirim untuk verifikasi.');
    }

    public function perbaikiData()
    {
        $this->profile->update(['status_verifikasi' => 'belum_melengkapi']);
        $this->status_verifikasi = 'belum_melengkapi';
    }
}; ?>

<div class="p-6 bg-[#F8FAFC] min-h-screen">

    {{-- 1. STATE: BELUM MELENGKAPI DATA --}}
    @if($status_verifikasi === 'belum_melengkapi')
        <div class="max-w-3xl mx-auto py-10">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">📦</div>
                <h2 class="text-2xl font-bold text-gray-900">Registrasi Pemasok SCFS</h2>
                <p class="text-gray-500 mt-2 text-sm">Lengkapi dokumen gudang/usaha Anda untuk mulai menerima pesanan.</p>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nama Perusahaan/Grosir</label>
                            <input wire:model="nama_usaha" type="text" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 py-3 text-sm">
                            @error('nama_usaha') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">NIK Pemilik</label>
                            <input wire:model="nik" type="text" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 py-3 text-sm">
                            @error('nik') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Alamat Gudang Utama</label>
                            <input wire:model="alamat_gudang" type="text" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 py-3 text-sm">
                            @error('alamat_gudang') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">WhatsApp Aktif</label>
                            <input wire:model="no_hp" type="text" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 py-3 text-sm">
                            @error('no_hp') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:col-span-2">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Nama Bank</label>
                                <select wire:model="nama_bank" class="w-full rounded-xl border-blue-100 bg-blue-50/30 py-3 text-sm">
                                    <option value="">-- Pilih Bank --</option>
                                    @foreach($daftar_bank as $bank)
                                        <option value="{{ $bank }}">{{ $bank }}</option>
                                    @endforeach
                                </select>
                                @error('nama_bank') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Nomor Rekening</label>
                                <input wire:model="nomor_rekening" type="number" class="w-full rounded-xl border-blue-100 bg-blue-50/30 py-3 text-sm">
                                @error('nomor_rekening') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="border-2 border-dashed border-gray-100 rounded-2xl p-6 text-center">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-3">Upload KTP Pemilik</label>
                        <input type="file" wire:model="foto_ktp" id="upload-ktp" class="hidden">
                        <label for="upload-ktp" class="cursor-pointer bg-gray-50 px-4 py-2 rounded-lg text-xs font-bold text-gray-500 hover:bg-gray-100 transition">Pilih File KTP</label>
                        @if($foto_ktp) 
                            <img src="{{ $foto_ktp->temporaryUrl() }}" class="mt-4 h-32 mx-auto rounded-lg shadow-md"> 
                        @endif
                    </div>

                    <div class="border-2 border-dashed border-gray-100 rounded-2xl p-6 text-center">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-3">Upload Foto Usaha / Gudang</label>
                        <input type="file" wire:model="foto_usaha" id="upload-usaha" class="hidden">
                        <label for="upload-usaha" class="cursor-pointer bg-blue-50 px-4 py-2 rounded-lg text-xs font-bold text-blue-600 hover:bg-blue-100 transition inline-block">Pilih Foto Usaha</label>
                        @if($foto_usaha) 
                            <img src="{{ $foto_usaha->temporaryUrl() }}" class="mt-4 h-32 mx-auto rounded-lg shadow-md object-cover"> 
                        @endif
                    </div>

                    <button wire:click="submitOnboarding" wire:loading.attr="disabled" class="w-full bg-[#2463EB] text-white py-4 rounded-2xl font-black text-sm shadow-lg shadow-blue-100 hover:bg-blue-700">
                        <span wire:loading.remove wire:target="submitOnboarding">KIRIM DATA VERIFIKASI</span>
                        <span wire:loading wire:target="submitOnboarding">MEMPROSES...</span>
                    </button>
                </div>
            </div>
        </div>

    {{-- 2. STATE: TUNGGU REVIEW --}}
    @elseif($status_verifikasi === 'menunggu_review')
        <div class="flex items-center justify-center min-h-[80vh]">
            <div class="bg-white p-12 rounded-[40px] shadow-sm border border-gray-100 max-w-lg text-center">
                <div class="w-24 h-24 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-yellow-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h2 class="text-2xl font-black text-gray-800 mb-2">Sedang Diverifikasi</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-8">Tim sedang mengecek validitas gudang dan data Anda. Mohon tunggu maksimal 24 jam.</p>
                <div class="px-6 py-3 bg-gray-50 rounded-2xl text-[10px] font-black text-gray-400 uppercase tracking-widest">Status: Pending Review</div>
            </div>
        </div>

    {{-- 3. STATE: DITOLAK --}}
    @elseif($status_verifikasi === 'ditolak')
        <div class="flex items-center justify-center min-h-[80vh]">
            <div class="bg-white p-12 rounded-[40px] shadow-sm border border-red-100 max-w-lg text-center">
                <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">❌</div>
                <h2 class="text-2xl font-black text-gray-800 mb-2">Verifikasi Ditolak</h2>
                <div class="bg-red-50 p-4 rounded-2xl mb-8">
                    <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-1">Alasan Penolakan:</p>
                    <p class="text-sm text-red-700 font-medium italic">"{{ $catatan_penolakan }}"</p>
                </div>
                <button wire:click="perbaikiData" class="w-full bg-gray-900 text-white py-4 rounded-2xl font-black text-sm">
                    PERBAIKI DATA SEKARANG
                </button>
            </div>
        </div>

    {{-- 4. STATE: DISETUJUI (DASHBOARD BARU) --}}
    @elseif($status_verifikasi === 'disetujui')
        {{-- HEADER DASHBOARD --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <nav class="text-xs text-gray-400 mb-1">Home &nbsp; > &nbsp; Overview</nav>
                <h1 class="text-2xl font-bold text-gray-800">Dashboard {{ $nama_usaha }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <input type="text" placeholder="Cari pesanan, produk..." class="pl-10 pr-4 py-2 bg-white border-none rounded-xl text-sm shadow-sm focus:ring-2 focus:ring-blue-500 w-64">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button class="p-2.5 bg-white rounded-xl shadow-sm hover:bg-gray-50 relative">
                    <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </button>
                <a href="{{ route('pemasok.inventaris', ['action' => 'tambah']) }}" class="bg-orange-600 text-white font-bold px-6 py-2.5 rounded-xl hover:bg-orange-700 transition-all shadow-lg shadow-orange-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Produk
                </a>
            </div>
        </div>

        {{-- TOP STATS CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-[#2463EB] rounded-3xl p-6 text-white shadow-xl shadow-blue-100 relative overflow-hidden flex flex-col justify-between">
                <div class="flex justify-between items-start mb-8">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                    </div>
                    <span class="bg-white/20 px-2 py-1 rounded-full text-[10px] flex items-center gap-1 font-bold">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg> +15%
                    </span>
                </div>
                <div>
                    <p class="text-blue-100 text-sm mb-1">Total Penjualan Grosir</p>
                    <h2 class="text-3xl font-bold mb-4">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</h2>
                    <div class="flex items-center justify-between">
                        <p class="text-blue-200 text-[10px]">Update terakhir: Barusan</p>
                        {{-- LINK KE DAFTAR PESANAN --}}
                        <a href="{{ route('pemasok.pesanan-masuk') }}" wire:navigate class="text-white text-xs font-bold flex items-center gap-1 hover:text-blue-200 transition-colors">
                            Lihat Pesanan <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-[#FFF9EE] rounded-3xl p-6 border border-orange-50 shadow-sm">
                <div class="flex justify-between items-start mb-8">
                    <div class="p-2 bg-orange-100 rounded-lg text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </div>
                    <span class="bg-orange-100 text-orange-600 px-3 py-1 rounded-full text-[10px] font-bold uppercase">Urgent</span>
                </div>
                <p class="text-gray-500 text-sm mb-1">Pesanan Perlu Dikirim</p>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">{{ $pesananPerluDikirim }} <span class="text-lg font-medium text-gray-400">Pesanan</span></h2>
                <a href="{{ route('pemasok.pengiriman') }}" wire:navigate class="text-blue-600 text-xs font-bold flex items-center gap-1 hover:text-blue-800 transition-colors">Lihat Detail <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></a>
            </div>

            <a href="{{ route('pemasok.inventaris', ['kritis' => true]) }}" class="block bg-white p-6 rounded-2xl shadow-sm border border-red-100 hover:bg-red-50 transition-colors cursor-pointer">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-500 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <span class="px-2.5 py-1 bg-orange-100 text-orange-700 text-[10px] font-black uppercase tracking-wider rounded-lg">Perlu Restock</span>
                </div>
                <p class="text-sm font-bold text-gray-500 mb-1">Stok Menipis</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-3xl font-black text-gray-800">5</h3>
                    <span class="text-sm font-bold text-gray-400">SKU</span>
                </div>
            </a>
        </div>

        {{-- MAIN CONTENT GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- CHART SECTION --}}
            <div class="lg:col-span-2 bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-10">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Tren Penjualan per Kategori</h3>
                        <p class="text-gray-400 text-xs">Distribusi pendapatan bulan ini</p>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row items-center justify-around gap-8">
                    <div class="relative w-48 h-48">
                        <svg viewBox="0 0 36 36" class="w-full h-full transform -rotate-90">
                            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#E2E8F0" stroke-width="4"></circle>
                            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#2463EB" stroke-width="4" stroke-dasharray="45 100"></circle>
                            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#60A5FA" stroke-width="4" stroke-dasharray="30 100" stroke-dashoffset="-45"></circle>
                            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#94A3B8" stroke-width="4" stroke-dasharray="25 100" stroke-dashoffset="-75"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-black text-gray-800">100%</span>
                            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Total</span>
                        </div>
                    </div>

                    <div class="space-y-4 w-full md:w-64">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                                <span class="text-sm font-medium text-gray-600">Makanan</span>
                            </div>
                            <span class="text-sm font-bold text-gray-800">45%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-blue-400"></span>
                                <span class="text-sm font-medium text-gray-600">Kosmetik</span>
                            </div>
                            <span class="text-sm font-bold text-gray-800">30%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SIDEBAR: QUICK ACTIONS & ACTIVITY --}}
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <a href="{{ route('pemasok.inventaris', ['action' => 'tambah']) }}" class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-blue-50 border border-gray-100 hover:border-blue-100 rounded-2xl transition-all group">
                        <div class="w-12 h-12 bg-white text-blue-500 rounded-full shadow-sm flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-xs font-black text-gray-500 uppercase tracking-widest group-hover:text-blue-600">PRODUK</span>
                    </a>

                    <a href="{{ route('pemasok.inventaris') }}" class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-green-50 border border-gray-100 hover:border-green-100 rounded-2xl transition-all group">
                        <div class="w-12 h-12 bg-white text-green-500 rounded-full shadow-sm flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <span class="text-xs font-black text-gray-500 uppercase tracking-widest group-hover:text-green-600">STOK</span>
                    </a>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-800 text-lg">Aktivitas Terkini</h3>
                    </div>
                    <div class="space-y-6">
                        @forelse($aktivitas as $log)
                        <div class="flex gap-4">
                            <div class="relative">
                                <div class="w-2 h-2 rounded-full mt-1.5 bg-blue-600"></div>
                                @if(!$loop->last) <div class="absolute top-4 left-[3px] w-[1px] h-full bg-gray-100"></div> @endif
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-800">Pesanan Baru #{{ $log->invoice_number }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5 line-clamp-1">Dari {{ $log->item_description }}</p>
                                <p class="text-[10px] text-gray-300 mt-1 uppercase font-medium">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @empty
                        <p class="text-xs text-gray-400 text-center py-4">Tidak ada aktivitas terbaru.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>