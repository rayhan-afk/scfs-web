<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\MerchantProfile;
use Illuminate\Support\Facades\Hash;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    // Fitur Pencarian & Filter
    public $search = '';
    public $filterStatus = 'Semua'; 

    // Variabel Form Tambah Merchant
    public $isAddModalOpen = false;
    public $nama_kantin, $nama_pemilik, $no_hp, $lokasi_blok, $info_pencairan, $persentase_bagi_hasil = 10;
    public $email, $password; 

    public function getMerchantsProperty()
    {
        $query = User::where('role', 'merchant')
                     ->has('merchantProfile') 
                     ->with('merchantProfile');

        if ($this->filterStatus !== 'Semua') {
            $status = strtolower($this->filterStatus);
            $query->whereHas('merchantProfile', function($q) use ($status) {
                $q->where('status_toko', $status);
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('merchantProfile', function($q2) {
                    $q2->where('nama_kantin', 'like', '%' . $this->search . '%')
                       ->orWhere('nama_pemilik', 'like', '%' . $this->search . '%')
                       ->orWhere('lokasi_blok', 'like', '%' . $this->search . '%');
                });
            });
        }

        return $query->latest()->get();
    }

    public function getStatsProperty()
    {
        return [
            'total_kantin' => MerchantProfile::count(),
            'total_token' => MerchantProfile::sum('saldo_token'),
            'total_tagihan' => MerchantProfile::sum('tagihan_setoran_tunai'),
        ];
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->isAddModalOpen = true;
    }

    public function closeAddModal()
    {
        $this->isAddModalOpen = false;
    }

    public function resetForm()
    {
        $this->reset(['nama_kantin', 'nama_pemilik', 'no_hp', 'lokasi_blok', 'info_pencairan', 'email', 'password']);
        $this->persentase_bagi_hasil = 10; 
    }

    public function saveMerchant()
    {
        $this->validate([
            'nama_kantin' => 'required|string|max:255',
            'nama_pemilik' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $this->nama_pemilik,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'merchant',
        ]);

        MerchantProfile::create([
            'user_id' => $user->id,
            'nama_kantin' => $this->nama_kantin,
            'nama_pemilik' => $this->nama_pemilik,
            'no_hp' => $this->no_hp,
            'lokasi_blok' => $this->lokasi_blok,
            'info_pencairan' => $this->info_pencairan, // Simpan info rekening/E-wallet
            'persentase_bagi_hasil' => $this->persentase_bagi_hasil,
            'status_toko' => 'tutup', 
        ]);

        $this->closeAddModal();
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">
    
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Manajemen Merchant (Kantin)</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola data kantin, pantau saldo token, dan tagihan bagi hasil secara *real-time*.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-0.5">Kantin Terdaftar</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ $this->stats['total_kantin'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Saldo Token (Belum Cair)</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_token'], 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Tagihan Tunai Kantin</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_tagihan'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto flex-1">
            <div class="relative w-full md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari nama kantin atau pemilik..." 
                    class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 transition">
            </div>

            <div class="relative w-full md:w-40">
                <select wire:model.live="filterStatus" class="appearance-none w-full py-2.5 pl-4 pr-10 text-sm font-medium text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition">
                    <option value="Semua">Semua Status</option>
                    <option value="Buka">Sedang Buka</option>
                    <option value="Tutup">Tutup</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <button wire:click="openAddModal" class="w-full md:w-auto px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium text-sm shadow-sm transition flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Kantin
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Kantin & Pemilik</th>
                        <th class="px-6 py-4">Lokasi Blok</th>
                        <th class="px-6 py-4 text-center">Bagi Hasil</th>
                        <th class="px-6 py-4 text-right">Saldo Token (Milik Kantin)</th>
                        <th class="px-6 py-4 text-right">Tagihan Tunai (Ke LKBB)</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->merchants as $merchant)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl flex items-center justify-center text-lg shadow-sm font-bold bg-amber-50 text-amber-600 border border-amber-100">
                                    🏪
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $merchant->merchantProfile?->nama_kantin ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">Pemilik: <span class="font-medium text-gray-700">{{ $merchant->merchantProfile?->nama_pemilik ?? '-' }}</span></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm font-medium text-gray-700">
                            {{ $merchant->merchantProfile?->lokasi_blok ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-1 rounded-md font-bold border border-gray-200">
                                {{ $merchant->merchantProfile?->persentase_bagi_hasil ?? 0 }}%
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-emerald-600">
                                Rp {{ number_format($merchant->merchantProfile?->saldo_token ?? 0, 0, ',', '.') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold {{ ($merchant->merchantProfile?->tagihan_setoran_tunai ?? 0) > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                Rp {{ number_format($merchant->merchantProfile?->tagihan_setoran_tunai ?? 0, 0, ',', '.') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if(($merchant->merchantProfile?->status_toko ?? 'tutup') == 'buka')
                                <span class="bg-emerald-100 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-emerald-200 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Buka
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-gray-200">
                                    Tutup
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.merchant.detail', $merchant->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                Detail & Keuangan
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-4xl mb-3">🏪</div>
                            <p class="text-gray-500 text-sm font-medium">Belum ada data kantin yang terdaftar.</p>
                            <p class="text-gray-400 text-xs mt-1">Klik tombol "Tambah Kantin" untuk memulai.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($isAddModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Daftarkan Kantin Baru
                </h3>
                <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="bg-blue-50 border border-blue-100 text-blue-700 text-xs p-3 rounded-xl flex items-start gap-2">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p>Mendaftarkan kantin otomatis akan membuatkan Akun Login (Email & Password) agar Ibu/Bapak kantin bisa mengakses aplikasi Kasir.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Kantin / Warung</label>
                        <input wire:model="nama_kantin" type="text" placeholder="Cth: Ayam Geprek Bu Ani" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Pemilik</label>
                        <input wire:model="nama_pemilik" type="text" placeholder="Cth: Ibu Ani" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No HP (WhatsApp)</label>
                        <input wire:model="no_hp" type="text" placeholder="Cth: 0812..." class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Lokasi / Blok</label>
                        <input wire:model="lokasi_blok" type="text" placeholder="Cth: Kantin Timur" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Info Bank / E-Wallet (Tujuan Pencairan)</label>
                    <input wire:model="info_pencairan" type="text" placeholder="Cth: GoPay 0812... a.n Ibu Ani" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                </div>

                <hr class="border-gray-100 my-2">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email (Untuk Login)</label>
                        <input wire:model="email" type="email" placeholder="kantin.ani@scfs.com" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password Sementara</label>
                        <input wire:model="password" type="password" placeholder="Minimal 6 karakter" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeAddModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="saveMerchant" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-sm focus:ring-4 focus:ring-blue-100">Daftarkan Kantin</button>
            </div>
        </div>
    </div>
    @endif

</div>