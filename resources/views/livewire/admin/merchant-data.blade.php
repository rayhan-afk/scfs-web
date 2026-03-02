<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\MerchantProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

new 
#[Layout('layouts.app')] 
class extends Component {
    use WithPagination;

    public string $search = '';
    
    // Tab Navigation State (Ditambah 'semua')
    public string $activeTab = 'semua';
    private const ALLOWED_TABS = ['semua', 'disetujui', 'menunggu_review', 'ditolak'];

    // Modal Form State
    public bool $isAddModalOpen = false;
    public $nama_kantin, $nama_pemilik, $nik, $no_hp, $lokasi_blok, $info_pencairan;
    public $persentase_bagi_hasil = 10;
    public $email, $password; 

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::ALLOWED_TABS, true)) {
            $this->activeTab = $tab;
            $this->resetPage(); 
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function merchants()
    {
        $query = User::where('role', 'merchant')
            ->whereHas('merchantProfile', function (Builder $q) {
                
                // Logika Tab Dinamis
                if ($this->activeTab !== 'semua') {
                    $q->where('status_verifikasi', $this->activeTab);
                } else {
                    // Jika tab "Semua", tetap sembunyikan yang belum mengisi form (Data Sampah)
                    $q->where('status_verifikasi', '!=', 'belum_melengkapi');
                }

                if (!empty($this->search)) {
                    $search = '%' . trim($this->search) . '%';
                    $q->where(function($q2) use ($search) {
                        $q2->where('nama_kantin', 'like', $search)
                           ->orWhere('nama_pemilik', 'like', $search)
                           ->orWhere('lokasi_blok', 'like', $search);
                    });
                }
            })
            ->with('merchantProfile');

        return $query->latest()->paginate(10);
    }

    #[Computed]
    public function stats()
    {
        $tabCounts = MerchantProfile::select('status_verifikasi', DB::raw('count(*) as total'))
            ->groupBy('status_verifikasi')
            ->pluck('total', 'status_verifikasi')
            ->toArray();

        // Mengamankan data dari null dan menjumlahkan total selain yang 'belum_melengkapi'
        $countDisetujui = $tabCounts['disetujui'] ?? 0;
        $countMenunggu  = $tabCounts['menunggu_review'] ?? 0;
        $countDitolak   = $tabCounts['ditolak'] ?? 0;
        $countSemua     = $countDisetujui + $countMenunggu + $countDitolak;

        return [
            'count_semua'     => $countSemua,
            'count_disetujui' => $countDisetujui,
            'count_menunggu'  => $countMenunggu,
            'count_ditolak'   => $countDitolak,

            'total_token'   => MerchantProfile::where('status_verifikasi', 'disetujui')->sum('saldo_token'),
            'total_tagihan' => MerchantProfile::where('status_verifikasi', 'disetujui')->sum('tagihan_setoran_tunai'),
        ];
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->isAddModalOpen = true;
    }

    public function closeAddModal(): void
    {
        $this->isAddModalOpen = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->reset(['nama_kantin', 'nama_pemilik', 'nik', 'no_hp', 'lokasi_blok', 'info_pencairan', 'email', 'password']);
        $this->persentase_bagi_hasil = 10; 
    }

    public function saveMerchant(): void
    {
        $this->validate([
            'nama_kantin'           => 'required|string|max:255',
            'nama_pemilik'          => 'required|string|max:255',
            'nik'                   => 'required|numeric|digits_between:15,17', 
            'no_hp'                 => 'required|string|max:20',
            'lokasi_blok'           => 'required|string|max:255',
            'info_pencairan'        => 'required|string|max:255',
            'persentase_bagi_hasil' => 'required|numeric|min:0|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:6',
        ]);

        try {
            DB::transaction(function () {
                $user = User::create([
                    'name'     => $this->nama_pemilik,
                    'email'    => $this->email,
                    'password' => Hash::make($this->password),
                    'role'     => 'merchant',
                ]);

                MerchantProfile::create([
                    'user_id'               => $user->id,
                    'nama_kantin'           => $this->nama_kantin,
                    'nama_pemilik'          => $this->nama_pemilik,
                    'nik'                   => $this->nik,
                    'no_hp'                 => $this->no_hp,
                    'lokasi_blok'           => $this->lokasi_blok,
                    'info_pencairan'        => $this->info_pencairan,
                    'persentase_bagi_hasil' => $this->persentase_bagi_hasil,
                    'status_verifikasi'     => 'disetujui', 
                    'status_toko'           => 'tutup',
                    'saldo_token'           => 0,
                    'tagihan_setoran_tunai' => 0,
                ]);
            });

            $this->closeAddModal();
            session()->flash('success', 'Kantin berhasil didaftarkan dan langsung Aktif!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal daftar merchant via Admin: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan sistem saat menyimpan data.');
        }
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Manajemen Mitra Kantin</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola data kantin, pantau saldo, dan verifikasi pendaftaran mandiri.</p>
        </div>
        <button wire:click="openAddModal" class="w-full md:w-auto px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold text-sm shadow-sm transition flex items-center justify-center gap-2 focus:ring-4 focus:ring-blue-100">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Kantin Instan
        </button>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3 rounded-xl flex items-center gap-2 shadow-sm">
        <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-xl flex items-center gap-2 shadow-sm">
        <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-0.5">Kantin Aktif</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ $this->stats['count_disetujui'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Saldo Token Kantin</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_token'], 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Piutang LKBB (Setoran)</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_tagihan'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation & Search --}}
    <div class="bg-white p-3 rounded-2xl border border-gray-200 shadow-sm flex flex-col xl:flex-row justify-between items-center gap-4">
        
        <div class="inline-flex gap-1 overflow-x-auto max-w-full w-full xl:w-auto p-1 bg-gray-50 rounded-xl border border-gray-100">
            
            <button wire:click="setTab('semua')" 
                class="flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap {{ $activeTab === 'semua' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                📋 Semua Status
                <span class="ml-2 px-2 py-0.5 rounded-md text-[10px] {{ $activeTab === 'semua' ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' }}">{{ $this->stats['count_semua'] }}</span>
            </button>

            <button wire:click="setTab('disetujui')" 
                class="flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap {{ $activeTab === 'disetujui' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                🟢 Kantin Aktif 
                <span class="ml-2 px-2 py-0.5 rounded-md text-[10px] {{ $activeTab === 'disetujui' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">{{ $this->stats['count_disetujui'] }}</span>
            </button>
            
            <button wire:click="setTab('menunggu_review')" 
                class="flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap {{ $activeTab === 'menunggu_review' ? 'bg-white text-yellow-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                ⏳ Antrean Verifikasi 
                <span class="ml-2 px-2 py-0.5 rounded-md text-[10px] {{ $activeTab === 'menunggu_review' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-600' }}">{{ $this->stats['count_menunggu'] }}</span>
            </button>

            <button wire:click="setTab('ditolak')" 
                class="flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap {{ $activeTab === 'ditolak' ? 'bg-white text-red-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                🔴 Ditolak 
                <span class="ml-2 px-2 py-0.5 rounded-md text-[10px] {{ $activeTab === 'ditolak' ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-600' }}">{{ $this->stats['count_ditolak'] }}</span>
            </button>
        </div>

        <div class="relative w-full xl:w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari kantin atau lokasi..." 
                class="w-full py-2 pl-9 pr-4 text-sm text-gray-700 bg-white border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
        </div>
    </div>

    {{-- Tabel Data --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Kantin & Pemilik</th>
                        <th class="px-6 py-4">Kontak & Lokasi</th>
                        <th class="px-6 py-4 text-center">Status Verifikasi</th>
                        <th class="px-6 py-4 text-right">Saldo / Keuangan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->merchants as $merchant)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl flex items-center justify-center text-sm font-bold bg-blue-50 text-blue-700 border border-blue-100 flex-shrink-0">
                                    {{ strtoupper(substr($merchant->merchantProfile->nama_kantin ?? 'K', 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">
                                        {{ $merchant->merchantProfile->nama_kantin ?? 'Belum Diatur' }}
                                    </div>
                                    <div class="text-[10px] text-gray-500 mt-0.5">
                                        Pemilik: {{ $merchant->merchantProfile->nama_pemilik ?? $merchant->name }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-700">{{ $merchant->merchantProfile->no_hp ?? '-' }}</div>
                            <div class="text-[10px] text-gray-500">{{ $merchant->merchantProfile->lokasi_blok ?? '-' }}</div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if(($merchant->merchantProfile->status_verifikasi ?? '') === 'disetujui')
                                <span class="bg-emerald-50 text-emerald-600 border border-emerald-100 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Aktif</span>
                            @elseif(($merchant->merchantProfile->status_verifikasi ?? '') === 'menunggu_review')
                                <span class="bg-yellow-50 text-yellow-600 border border-yellow-100 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Pending</span>
                            @elseif(($merchant->merchantProfile->status_verifikasi ?? '') === 'ditolak')
                                <span class="bg-red-50 text-red-600 border border-red-100 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Ditolak</span>
                            @else
                                <span class="bg-gray-100 text-gray-500 border border-gray-200 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Tidak Diketahui</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            @if(($merchant->merchantProfile->status_verifikasi ?? '') === 'disetujui')
                                <div class="text-sm font-bold text-emerald-600">
                                    Rp {{ number_format($merchant->merchantProfile->saldo_token ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-[10px] text-gray-500 mt-0.5">Bagi Hasil: {{ $merchant->merchantProfile->persentase_bagi_hasil ?? 0 }}%</div>
                            @else
                                <span class="text-xs text-gray-400 italic">Belum tersedia</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.merchant.detail', $merchant->id) }}" wire:navigate
                                class="inline-flex items-center px-3 py-1.5 text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                Lihat Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="text-5xl mb-3 opacity-50">🏪</div>
                            <p class="text-gray-500 text-sm font-medium">Tidak ada data kantin di tab ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->merchants->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $this->merchants->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Tambah Kantin (SAMA SEPERTI SEBELUMNYA) --}}
    @if($isAddModalOpen)
        {{-- ... Kodingan modal dari file sebelumnya tidak ada yang diubah ... --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Daftarkan Kantin (Aktivasi Instan)
                    </h3>
                    <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 transition p-1.5 rounded-lg hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="bg-blue-50 border border-blue-100 text-blue-800 text-xs p-3 rounded-xl flex items-start gap-2 shadow-sm">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p>Kantin yang ditambahkan melalui panel Admin ini akan langsung berstatus <strong>"Disetujui/Aktif"</strong> (Bypass Verifikasi LKBB).</p>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Kantin / Warung *</label>
                            <input wire:model="nama_kantin" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                            @error('nama_kantin') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Pemilik *</label>
                            <input wire:model="nama_pemilik" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                            @error('nama_pemilik') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Induk Kependudukan (NIK) *</label>
                            <input wire:model="nik" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                            @error('nik') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No HP (WhatsApp) *</label>
                            <input wire:model="no_hp" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                            @error('no_hp') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Lokasi / Blok *</label>
                            <input wire:model="lokasi_blok" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                            @error('lokasi_blok') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Persentase LKBB (%) *</label>
                            <input wire:model="persentase_bagi_hasil" type="number" min="0" max="100" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                            @error('persentase_bagi_hasil') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1.5">Info Rekening / E-Wallet (Pencairan) *</label>
                        <input wire:model="info_pencairan" type="text" class="w-full text-sm rounded-xl border-blue-200 focus:border-blue-500 focus:ring-blue-500 bg-blue-50/30 py-2.5">
                        @error('info_pencairan') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <hr class="border-gray-100">

                    <div class="grid grid-cols-2 gap-5 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email (Akses Login) *</label>
                            <input wire:model="email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-gray-500 focus:ring-gray-500 bg-white py-2">
                            @error('email') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password Sementara *</label>
                            <input wire:model="password" type="password" class="w-full text-sm rounded-xl border-gray-300 focus:border-gray-500 focus:ring-gray-500 bg-white py-2">
                            @error('password') <span class="text-red-500 text-[10px] mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                    <button wire:click="closeAddModal" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition focus:ring-4 focus:ring-gray-100">Batal</button>
                    <button wire:click="saveMerchant" wire:loading.attr="disabled" class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-sm disabled:opacity-70 focus:ring-4 focus:ring-blue-100">
                        <span wire:loading.remove wire:target="saveMerchant">Simpan & Aktifkan</span>
                        <span wire:loading wire:target="saveMerchant">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>