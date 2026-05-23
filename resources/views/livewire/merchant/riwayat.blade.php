<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Carbon\Carbon;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    use WithPagination;

    public $search = '';
    public $dateRange = 'today'; // Default ke hari ini agar laporannya fokus ke uang harian
    public $typeFilter = 'semua'; 

    public function updatedSearch() { $this->resetPage(); }
    public function updatedDateRange() { $this->resetPage(); }
    public function updatedTypeFilter() { $this->resetPage(); }

    #[Computed]
    public function baseQuery()
    {
        $query = Transaction::where('merchant_id', Auth::id())
                    ->whereIn('status', ['sukses', 'lunas'])
                    ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai']);

        if (!empty($this->search)) {
            $query->where('order_id', 'like', '%' . trim($this->search) . '%');
        }
        if ($this->typeFilter === 'digital') {
            $query->where('type', 'pembayaran_makanan');
        } elseif ($this->typeFilter === 'tunai') {
            $query->where('type', 'pembayaran_makanan_tunai');
        }

        $now = Carbon::now();
        if ($this->dateRange === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($this->dateRange === 'month') {
            $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
        } elseif ($this->dateRange === 'year') {
            $query->whereYear('created_at', $now->year);
        }

        return $query;
    }

    #[Computed]
    public function transactions()
    {
        return (clone $this->baseQuery)->latest()->paginate(15);
    }

    /**
     * UPDATE LOGIKA KEUANGAN (MENGIKUTI KONSEP TOTAL POKOK + FEE LKBB)
     */
    #[Computed]
    public function summary()
    {
        $allTx = (clone $this->baseQuery)->get();
        $tunaiTx = $allTx->where('type', 'pembayaran_makanan_tunai');
        $digitalTx = $allTx->where('type', 'pembayaran_makanan');

        // Total Volume Jualan
        $totalVolume = $allTx->sum('total_amount');
        
        // 1. Hak Merchant (Laba Bersih Kantin) = (Total Jual - Harga Pokok LKBB) - Fee LKBB
        $hakMerchantDigital = $digitalTx->sum(function ($trx) {
            return ($trx->total_amount - $trx->total_pokok) - $trx->fee_lkbb;
        });
        $hakMerchantTunai = $tunaiTx->sum(function ($trx) {
            return ($trx->total_amount - $trx->total_pokok) - $trx->fee_lkbb;
        });
        $totalHakMerchant = $hakMerchantDigital + $hakMerchantTunai;

        // 2. Hak LKBB (Pengembalian Modal + Keuntungan LKBB) = Harga Pokok + Fee LKBB
        $hakLkbbDigital = $digitalTx->sum(function ($trx) {
            return $trx->total_pokok + $trx->fee_lkbb;
        });
        $hakLkbbTunai = $tunaiTx->sum(function ($trx) {
            return $trx->total_pokok + $trx->fee_lkbb;
        });

        // 3. Uang Fisik di Laci Kantin
        $uangFisikLaci = $tunaiTx->sum('total_amount');

        return [
            'total_transaksi'     => $allTx->count(),
            'total_volume'        => $totalVolume,
            'laba_bersih_kantin'  => $totalHakMerchant,
            'uang_fisik_di_laci'  => $uangFisikLaci,
            'setoran_wajib_lkbb'  => $hakLkbbTunai, // Uang fisik LKBB yang kebawa Kantin
        ];
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Laporan Keuangan Kantin</h2>
            <p class="text-gray-500 text-sm mt-1">Pantau transparansi laba bersih dan uang setoran modal titipan LKBB Anda.</p>
        </div>
        
        <button class="px-4 py-2.5 bg-[#059669] border border-emerald-200 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center gap-2 hover:bg-emerald-700 group">
            <svg class="w-4 h-4 text-emerald-100 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Unduh Laporan Excel
        </button>
    </div>

    {{-- KARTU RINGKASAN BARU (LEBIH RELEVAN) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        
        {{-- Total Jualan Keseluruhan --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center relative overflow-hidden">
             <div class="absolute right-0 top-0 opacity-5 w-24 h-24">
                 <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
             </div>
             <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Transaksi Masuk</p>
             <h3 class="text-2xl font-extrabold text-gray-900">Rp {{ number_format($this->summary['total_volume'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-gray-500 font-medium mt-1">Dari <strong>{{ number_format($this->summary['total_transaksi'], 0, ',', '.') }}</strong> Struk/Pesanan</p>
        </div>

        {{-- Laba Bersih Kantin --}}
        <div class="bg-gradient-to-br from-[#059669] to-teal-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200/50 flex flex-col justify-center">
             <p class="text-[10px] font-bold text-emerald-100 uppercase tracking-wider mb-1">Laba Bersih Kantin</p>
             <h3 class="text-2xl font-black">Rp {{ number_format($this->summary['laba_bersih_kantin'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-emerald-50 mt-1 font-medium italic">Hak murni kantin (Modal & Profit sdh dipotong)</p>
        </div>

        {{-- Uang Tunai di Laci --}}
        <div class="bg-white rounded-2xl p-5 border border-sky-200 shadow-sm flex flex-col justify-center bg-sky-50/20">
             <p class="text-[10px] font-bold text-sky-600 uppercase tracking-wider mb-1">Uang Fisik (Laci Kasir)</p>
             <h3 class="text-2xl font-extrabold text-gray-900">Rp {{ number_format($this->summary['uang_fisik_di_laci'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-sky-600 font-bold mt-1 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Hasil dari pembeli umum
             </p>
        </div>

        {{-- Setoran Wajib LKBB --}}
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg shadow-amber-200/50 flex flex-col justify-center relative overflow-hidden group">
             <p class="text-[10px] font-bold text-amber-100 uppercase tracking-wider mb-1">Setoran Wajib ke LKBB</p>
             <h3 class="text-2xl font-black">Rp {{ number_format($this->summary['setoran_wajib_lkbb'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-amber-50 mt-1 font-bold italic">Pengembalian Modal Barang + Profit (Tunai)</p>
        </div>
    </div>

    {{-- FILTER & PENCARIAN --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col lg:flex-row gap-4 mb-6">
        
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Cari berdasarkan Order ID..." 
                class="w-full py-2.5 pl-11 pr-4 text-sm text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100 transition">
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <select wire:model.live="typeFilter" class="py-2.5 pl-4 pr-10 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100 cursor-pointer">
                <option value="semua">Semua Jalur Kasir</option>
                <option value="digital">💳 Beasiswa Mahasiswa</option>
                <option value="tunai">💵 Umum (Tunai Laci)</option>
            </select>

            <select wire:model.live="dateRange" class="py-2.5 pl-4 pr-10 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100 cursor-pointer">
                <option value="today">Hari Ini</option>
                <option value="month">Bulan Ini</option>
                <option value="year">Tahun Ini</option>
                <option value="all">Sepanjang Waktu</option>
            </select>
        </div>
    </div>

    {{-- TABEL DATA TRANSAKSI (DENGAN RINCIAN SPLIT) --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead class="bg-gray-50/80 text-gray-400 text-[10px] uppercase font-extrabold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-4">Waktu & ID</th>
                        <th class="px-5 py-4">Menu Terjual</th>
                        <th class="px-5 py-4 text-center">Harga Jual</th>
                        <th class="px-5 py-4 text-center">Modal Barang (HPP)</th>
                        <th class="px-5 py-4 text-right">Laba Kantin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->transactions as $trx)
                        @php
                            // Perhitungan baris ini
                            $hakLKBB = $trx->total_pokok + $trx->fee_lkbb;
                            $labaKantin = $trx->total_amount - $hakLKBB;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            {{-- KOLOM 1: Waktu & ID --}}
                            <td class="px-5 py-4">
                                <div class="text-xs font-bold text-gray-900 font-mono">{{ $trx->order_id }}</div>
                                <div class="text-[10px] text-gray-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $trx->created_at->format('d M y, H:i') }}
                                </div>
                                @if($trx->type === 'pembayaran_makanan_tunai')
                                    <span class="inline-block mt-2 px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-[8px] font-extrabold uppercase border border-amber-200">💵 Laci Tunai</span>
                                @else
                                    <span class="inline-block mt-2 px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[8px] font-extrabold uppercase border border-blue-200">💳 QR Beasiswa</span>
                                @endif
                            </td>
                            
                            {{-- KOLOM 2: Deskripsi Menu --}}
                            <td class="px-5 py-4 max-w-[200px]">
                                <div class="text-xs font-medium text-gray-700 line-clamp-2 leading-relaxed">
                                    {{ str_replace(['[QR] ', '[TUNAI] '], '', $trx->description) }}
                                </div>
                            </td>
                            
                            {{-- KOLOM 3: Harga Jual (Uang Masuk) --}}
                            <td class="px-5 py-4 text-center">
                                <div class="text-sm font-extrabold text-gray-900">Rp{{ number_format($trx->total_amount, 0, ',', '.') }}</div>
                            </td>
                            
                            {{-- KOLOM 4: Potongan Modal & Fee (Hak LKBB) --}}
                            <td class="px-5 py-4 text-center">
                                <div class="text-xs font-bold text-rose-600 bg-rose-50 inline-block px-2.5 py-1 rounded-md border border-rose-100">
                                    -Rp {{ number_format($hakLKBB, 0, ',', '.') }}
                                </div>
                                @if($trx->type === 'pembayaran_makanan_tunai')
                                    <div class="text-[8px] text-rose-500 mt-1 font-bold">Jadi hutang setoran</div>
                                @else
                                    <div class="text-[8px] text-emerald-600 mt-1 font-bold">Dipotong Otomatis</div>
                                @endif
                            </td>

                            {{-- KOLOM 5: Laba Bersih Kantin --}}
                            <td class="px-5 py-4 text-right">
                                <div class="text-sm font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-100 inline-block px-3 py-1.5 rounded-lg shadow-sm">
                                    + Rp {{ number_format($labaKantin, 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="text-5xl mb-4 opacity-30 flex justify-center">🧾</div>
                                <h3 class="text-sm font-bold text-gray-600">Belum ada penjualan hari ini.</h3>
                                <p class="text-xs text-gray-400 mt-1">Ubah filter tanggal untuk melihat hari lainnya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($this->transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
                {{ $this->transactions->links() }}
            </div>
        @endif
    </div>

</div>