<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\User;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public $search = '';
    
    // State untuk Modal
    public $isDetailModalOpen = false;
    public $isTopupModalOpen = false;
    public $selectedUser = null;
    public $topupAmount = 0;

    // Reset halaman saat mengetik di pencarian
    public function updatedSearch()
    {
        $this->resetPage();
    }

   public function with()
    {
        return [
            'merchants' => User::where('role', 'merchant')
                // 1. Cek status_verifikasi di dalam tabel merchant_profiles
                ->whereHas('merchantProfile', function($query) {
                    $query->where('status_verifikasi', 'approved');
                })
                // 2. Pencarian berdasarkan nama user ATAU nama toko
                ->where(function($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                          ->orWhereHas('merchantProfile', function($q) {
                              $q->where('company_name', 'like', '%'.$this->search.'%');
                          });
                })
                ->with('merchantProfile')
                ->paginate(10)
        ];
    }

    // Fungsi Buka/Tutup Modal Detail Biodata
    public function openDetail($id)
    {
        $this->selectedUser = User::with('merchantProfile')->find($id);
        $this->isDetailModalOpen = true;
    }

    public function closeDetail()
    {
        $this->isDetailModalOpen = false;
        $this->selectedUser = null;
    }

    // Fungsi Buka/Tutup Modal Input Saldo
    public function openTopup($id)
    {
        $this->selectedUser = User::with('merchantProfile')->find($id);
        $this->topupAmount = null;
        $this->isTopupModalOpen = true;
    }

    public function closeTopup()
    {
        $this->isTopupModalOpen = false;
        $this->selectedUser = null;
    }

    // Fungsi Simpan Saldo / Limit
    public function submitTopup()
    {
        $this->validate([
            'topupAmount' => 'required|numeric|min:10000'
        ]);

        if($this->selectedUser && $this->selectedUser->merchantProfile) {
            $profile = $this->selectedUser->merchantProfile;
            $profile->credit_limit += $this->topupAmount; // Menambah Limit Saldo
            $profile->save();
            
            session()->flash('message', 'Saldo/Limit berhasil ditambahkan sebesar Rp ' . number_format($this->topupAmount, 0, ',', '.'));
            $this->closeTopup();
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Keuangan Merchant (Kantin)</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola limit saldo, top up, dan lihat riwayat keuangan merchant.</p>
        </div>
        
        <div class="w-72 relative">
            <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau toko..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative mb-4 shadow-sm">
            <strong class="font-bold">Sukses!</strong> {{ session('message') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-500 border-b border-gray-200 uppercase tracking-wider">
                    <th class="py-4 px-6 font-bold">Data Merchant</th>
                    <th class="py-4 px-6 font-bold">Kontak Pemilik</th>
                    <th class="py-4 px-6 font-bold">Limit / Saldo Terkini</th>
                    <th class="py-4 px-6 font-bold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-600 divide-y divide-gray-100">
                @forelse($merchants as $merchant)
                <tr class="hover:bg-blue-50/50 transition">
                    <td class="py-4 px-6">
                        <div class="font-bold text-gray-800 text-base">{{ $merchant->merchantProfile->company_name ?? 'Belum Set Nama Toko' }}</div>
                        <div class="text-xs text-gray-400 mt-1">ID: #MCT-{{ str_pad($merchant->id, 4, '0', STR_PAD_LEFT) }}</div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="font-medium text-gray-700">{{ $merchant->name }}</div>
                        <div class="text-xs text-gray-500">{{ $merchant->merchantProfile->phone ?? $merchant->email }}</div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="font-extrabold text-blue-600 text-lg">
                            Rp {{ number_format($merchant->merchantProfile->credit_limit ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="text-[10px] text-gray-400 uppercase tracking-wider mt-0.5">Sisa Limit Tersedia</div>
                    </td>
                    <td class="py-4 px-6 text-right">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openDetail({{ $merchant->id }})" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200 transition">
                                Detail Biodata
                            </button>
                            <button wire:click="openTopup({{ $merchant->id }})" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition shadow-sm">
                                + Input Saldo
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-10 text-gray-500">
                        Tidak ada data merchant yang ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($merchants->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                {{ $merchants->links() }}
            </div>
        @endif
    </div>

    @if($isDetailModalOpen && $selectedUser)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-lg text-gray-800">Detail Biodata Merchant</h3>
                <button wire:click="closeDetail" class="text-gray-400 hover:text-red-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <div class="p-6 space-y-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Nama Toko / Usaha</p>
                    <p class="font-bold text-gray-800 text-lg">{{ $selectedUser->merchantProfile->company_name ?? '-' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Nama Pemilik</p>
                        <p class="font-medium text-gray-800">{{ $selectedUser->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">No. Handphone</p>
                        <p class="font-medium text-gray-800">{{ $selectedUser->merchantProfile->phone ?? '-' }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Alamat Lengkap</p>
                    <p class="font-medium text-gray-800 bg-gray-50 p-3 rounded-lg border border-gray-100">{{ $selectedUser->merchantProfile->address ?? 'Belum ada data alamat' }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($isTopupModalOpen && $selectedUser)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-blue-50 text-blue-800">
                <h3 class="font-bold text-lg">Input Saldo / Limit</h3>
                <button wire:click="closeTopup" class="text-blue-400 hover:text-blue-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Masukkan nominal penambahan saldo/limit untuk merchant <strong>{{ $selectedUser->merchantProfile->company_name ?? $selectedUser->name }}</strong>.</p>
                
                <form wire:submit="submitTopup">
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nominal (Rp)</label>
                        <input wire:model="topupAmount" type="number" placeholder="Contoh: 5000000" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 font-bold text-lg text-gray-800">
                        @error('topupAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" wire:click="closeTopup" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-bold hover:bg-gray-200">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 shadow-sm">Simpan Saldo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>