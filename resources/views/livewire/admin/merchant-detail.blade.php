<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Withdrawal;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public User $user;
    public $activeTab = 'katalog'; 

    // Variabel Modal Edit Profil
    public $isEditModalOpen = false;
    public $edit_nama_kantin, $edit_nama_pemilik, $edit_lokasi_blok, $edit_persentase_bagi_hasil, $edit_status_toko;
    public $edit_email, $edit_no_hp, $edit_info_pencairan;

    // Variabel Modal Riwayat Harga
    public $isHistoryModalOpen = false;
    public $selectedProductHistory = null;
    public $selectedProductName = '';

    public function mount($id)
    {
        $this->user = User::with(['merchantProfile', 'merchantProducts.priceHistories'])->findOrFail($id);
    }

    // =====================================
    // FUNGSI TARIK DATA ASLI (BUKAN DUMMY)
    // =====================================
    #[Computed]
    public function riwayatPenjualan()
    {
        // Menarik data transaksi asli milik merchant ini
        return Transaction::with('user')
                ->where('merchant_id', $this->user->id)
                ->whereIn('status', ['sukses', 'lunas'])
                ->latest()
                ->limit(50) // Batasi 50 terakhir agar admin tidak lag
                ->get();
    }

    #[Computed]
    public function riwayatPencairan()
    {
        // Menarik data penarikan dana asli milik merchant ini
        return Withdrawal::where('merchant_id', $this->user->id)
                ->latest()
                ->limit(50)
                ->get();
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
            $this->edit_info_pencairan = $this->user->merchantProfile->info_pencairan ?? ''; 
            
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
                'info_pencairan' => $this->edit_info_pencairan, 
                'lokasi_blok' => $this->edit_lokasi_blok,
                'persentase_bagi_hasil' => $this->edit_persentase_bagi_hasil,
                'status_toko' => $this->edit_status_toko,
            ]);
        }

        $this->user->refresh();
        $this->closeEditModal();
        session()->flash('success', 'Data profil kantin berhasil diupdate.');
    }

    // =====================================
    // FUNGSI RIWAYAT HARGA (AUDIT TRAIL)
    // =====================================
    public function viewPriceHistory($productId)
    {
        $product = $this->user->merchantProducts->where('id', $productId)->first();
        if ($product) {
            $this->selectedProductName = $product->nama_produk;
            $this->selectedProductHistory = $product->priceHistories;
            $this->isHistoryModalOpen = true;
        }
    }

    public function closeHistoryModal()
    {
        $this->isHistoryModalOpen = false;
        $this->selectedProductHistory = null;
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    {{-- Header --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('admin.merchant.index') }}" class="hover:text-blue-600 transition">Data Merchant</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="font-medium text-gray-900">Detail Kantin</span>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm mb-4">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- KARTU PROFIL UTAMA --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-3xl shadow-inner border border-amber-200">🏪</div>
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

    {{-- KARTU INFO & KEUANGAN --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full relative overflow-hidden">
            <div class="flex items-center gap-2 mb-6 text-blue-700 font-bold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Informasi & Kontak Kantin
            </div>
            <div class="space-y-4 flex-1">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email Login</p>
                        <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No Handphone</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->merchantProfile->no_hp ?: '-' }}</p>
                    </div>
                </div>
                <hr class="border-gray-100 my-2">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Lokasi / Blok</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->merchantProfile->lokasi_blok ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Bagi Hasil LKBB</p>
                        <p class="text-blue-600 font-bold text-sm bg-blue-50 px-2 py-0.5 rounded-md inline-block">{{ $user->merchantProfile->persentase_bagi_hasil ?? 0 }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            <div class="relative z-10 flex-1 flex flex-col justify-center">
                <span class="text-emerald-50 text-[10px] font-bold tracking-wider uppercase mb-4">HAK KANTIN (SALDO TOKEN)</span>
                <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">Rp {{ number_format($user->merchantProfile->saldo_token ?? 0, 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            <div class="relative z-10 flex-1 flex flex-col justify-center">
                <span class="text-rose-50 text-[10px] font-bold tracking-wider uppercase mb-4">HAK LKBB (HUTANG TUNAI)</span>
                <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">Rp {{ number_format($user->merchantProfile->tagihan_setoran_tunai ?? 0, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    {{-- TAB NAVIGASI MULTI-FUNGSI --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4">
        <div class="flex border-b border-gray-100 px-6 gap-6 overflow-x-auto">
            <button wire:click="$set('activeTab', 'katalog')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'katalog' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">Katalog & Harga Produk</button>
            <button wire:click="$set('activeTab', 'penjualan')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'penjualan' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">Riwayat Penjualan (POS)</button>
            <button wire:click="$set('activeTab', 'pencairan')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'pencairan' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">Histori Settlement</button>
        </div>
        
        <div class="p-0 overflow-x-auto">
            
            {{-- TAB 1: KATALOG & PENGAWASAN HARGA --}}
            @if($activeTab == 'katalog')
            <div class="p-6 bg-blue-50/30 border-b border-gray-100 flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-xs text-blue-800 font-medium">Tabel ini digunakan Admin untuk memantau indikasi kecurangan margin profit. Jika Harga Modal disamakan dengan Harga Jual, Fee LKBB akan menjadi Rp 0.</p>
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Nama Menu / Produk</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4 text-right">Harga Pokok (Modal)</th>
                        <th class="px-6 py-4 text-right">Harga Jual (Kasir)</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Audit Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($user->merchantProducts as $produk)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-sm text-gray-900">{{ $produk->nama_produk }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-gray-600 capitalize">
                                {{ str_replace('_', ' ', $produk->kategori) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-700">Rp {{ number_format($produk->harga_pokok, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-sm font-extrabold text-emerald-600">Rp {{ number_format($produk->harga_jual, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($produk->is_tersedia)
                                    <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-[10px] font-bold">Aktif</span>
                                @else
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-[10px] font-bold">Arsip</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="viewPriceHistory({{ $produk->id }})" class="text-[10px] font-bold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">
                                    Riwayat Ubah Harga
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center text-gray-500">Merchant ini belum menambahkan satupun produk ke Katalog.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            {{-- TAB 2: PENJUALAN ASLI --}}
            @if($activeTab == 'penjualan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Pesanan & Waktu</th>
                        <th class="px-6 py-4">Pembeli & Metode</th>
                        <th class="px-6 py-4 text-right">Nilai Transaksi</th>
                        <th class="px-6 py-4 text-right">Potongan LKBB ({{ $user->merchantProfile->persentase_bagi_hasil ?? 0 }}%)</th>
                        <th class="px-6 py-4 text-right">Diterima Kantin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->riwayatPenjualan as $trx)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-xs text-gray-900 font-mono">{{ $trx->order_id }}</div>
                            <div class="text-[10px] text-gray-500 mt-1">{{ $trx->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-sm text-gray-900">{{ $trx->user->name ?? 'Pembeli Umum' }}</div>
                            <div class="text-[10px] mt-0.5 font-bold uppercase tracking-wider {{ $trx->type == 'pembayaran_makanan_tunai' ? 'text-amber-600' : 'text-blue-600' }}">
                                {{ $trx->type == 'pembayaran_makanan_tunai' ? '💵 TUNAI / CASH' : '💳 QR BEASISWA' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-extrabold text-gray-900">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-rose-500">- Rp {{ number_format($trx->fee_lkbb, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-emerald-600">Rp {{ number_format($trx->total_amount - $trx->fee_lkbb, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat penjualan asli.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            {{-- TAB 3: PENCAIRAN ASLI --}}
            @if($activeTab == 'pencairan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Pencairan & Waktu</th>
                        <th class="px-6 py-4">Tujuan Pencairan</th>
                        <th class="px-6 py-4 text-right">Nominal Tarik Bersih</th>
                        <th class="px-6 py-4">Status Pencairan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->riwayatPencairan as $wd)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-xs text-gray-900 font-mono">{{ $wd->nomor_pencairan }}</div>
                            <div class="text-[10px] text-gray-500 mt-1">{{ $wd->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-sm text-gray-700">{{ $wd->info_pencairan ?? 'Tidak Ada Info' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-extrabold text-gray-900">Rp {{ number_format($wd->nominal_bersih, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($wd->status == 'pending')
                                <span class="bg-amber-50 text-amber-600 px-2 py-1 rounded text-xs font-bold">Menunggu</span>
                            @elseif($wd->status == 'disetujui')
                                <span class="bg-emerald-50 text-emerald-600 px-2 py-1 rounded text-xs font-bold">Sukses</span>
                            @else
                                <span class="bg-rose-50 text-rose-600 px-2 py-1 rounded text-xs font-bold">Ditolak</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat penarikan dana.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- MODAL RIWAYAT HARGA (AUDIT TRAIL) --}}
    @if($isHistoryModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-900">Jejak Perubahan Harga</h3>
                    <p class="text-xs text-gray-500 mt-1">Produk: <span class="font-bold text-blue-600">{{ $selectedProductName }}</span></p>
                </div>
                <button wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-xl hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-0 max-h-[60vh] overflow-y-auto">
                @if($selectedProductHistory && count($selectedProductHistory) > 0)
                    <table class="w-full text-left">
                        <thead class="bg-white text-gray-400 text-[9px] uppercase font-bold tracking-wider sticky top-0 shadow-sm">
                            <tr>
                                <th class="px-5 py-3">Waktu Diubah</th>
                                <th class="px-5 py-3">Harga Pokok (Lama ➡️ Baru)</th>
                                <th class="px-5 py-3">Harga Jual (Lama ➡️ Baru)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($selectedProductHistory as $histori)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-xs font-mono text-gray-500">{{ $histori->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="line-through text-gray-400">{{ $histori->harga_pokok_lama ? 'Rp'.number_format($histori->harga_pokok_lama,0,',','.') : '-' }}</span> 
                                    <span class="mx-1 text-gray-300">➡️</span> 
                                    <span class="font-bold text-gray-800">Rp{{ number_format($histori->harga_pokok_baru,0,',','.') }}</span>
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="line-through text-gray-400">{{ $histori->harga_jual_lama ? 'Rp'.number_format($histori->harga_jual_lama,0,',','.') : '-' }}</span> 
                                    <span class="mx-1 text-gray-300">➡️</span> 
                                    <span class="font-bold text-emerald-600">Rp{{ number_format($histori->harga_jual_baru,0,',','.') }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 text-center">
                        <p class="text-gray-400 text-sm">Belum ada riwayat perubahan harga sejak produk dibuat.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL EDIT MERCHANT --}}
    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">Edit Data Kantin</h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:bg-gray-200 p-1.5 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            
            <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Nama Kantin</label><input wire:model="edit_nama_kantin" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"></div>
                    <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Nama Pemilik</label><input wire:model="edit_nama_pemilik" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Email</label><input wire:model="edit_email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"></div>
                    <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">No HP</label><input wire:model="edit_no_hp" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"></div>
                </div>
                <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Lokasi / Blok</label><input wire:model="edit_lokasi_blok" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"></div>
                <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl"><label class="block text-[10px] font-bold text-blue-500 uppercase mb-1.5">Info Rekening</label><input wire:model="edit_info_pencairan" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Bagi Hasil (%)</label><input wire:model="edit_persentase_bagi_hasil" type="number" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"></div>
                    <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Status Toko</label><select wire:model="edit_status_toko" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2"><option value="buka">Buka</option><option value="tutup">Tutup</option></select></div>
                </div>
            </div>
            
            <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">Batal</button>
                <button wire:click="updateMerchant" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm">Simpan Perubahan</button>
            </div>
        </div>
    </div>
    @endif

</div>