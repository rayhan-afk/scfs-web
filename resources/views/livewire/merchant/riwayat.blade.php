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
    public $dateRange = 'all'; 
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
     * UPDATE: Pemisahan Agregasi Fee Digital vs Fee Tunai
     */
    #[Computed]
    public function summary()
    {
        $queryDigital = (clone $this->baseQuery)->where('type', 'pembayaran_makanan');
        $queryTunai = (clone $this->baseQuery)->where('type', 'pembayaran_makanan_tunai');
        
        return [
            'total_volume'   => (clone $this->baseQuery)->sum('total_amount'),
            'total_transaksi'=> (clone $this->baseQuery)->count(),
            
            // DIPISAH!
            'fee_digital_lunas' => $queryDigital->sum('fee_lkbb'),
            'fee_tunai_hutang'  => $queryTunai->sum('fee_lkbb'),
        ];
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Riwayat Penjualan</h2>
            <p class="text-gray-500 text-sm mt-1">Pantau seluruh aliran kas masuk dari mesin POS Kantin Anda.</p>
        </div>
        
        <button class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Cetak Laporan
        </button>
    </div>

    {{-- UPDATE: METRIC CARDS SEKARANG ADA 4 KOTAK UNTUK TRANSPARANSI --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        
        {{-- Total Volume --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Volume Jualan</p>
                <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">Rp {{ number_format($this->summary['total_volume'], 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Transaksi Sukses --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-gray-50 text-gray-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Struk</p>
                <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">{{ number_format($this->summary['total_transaksi'], 0, ',', '.') }} <span class="text-sm font-medium text-gray-500">Struk</span></h3>
            </div>
        </div>

        {{-- Fee Digital (Tercoret) --}}
        <div class="bg-white rounded-2xl p-5 border border-blue-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Fee Digital (Otomatis)</p>
                <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">- Rp {{ number_format($this->summary['fee_digital_lunas'], 0, ',', '.') }}</h3>
                <p class="text-[9px] text-gray-400 italic leading-none mt-1">Sudah dipotong lunas</p>
            </div>
        </div>

        {{-- Fee Tunai (Hutang Fisik) --}}
        <div class="bg-rose-50 rounded-2xl p-5 border border-rose-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-white text-rose-600 rounded-xl shadow-sm">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Hutang Kasir (Tunai)</p>
                <h3 class="text-xl font-extrabold text-rose-700 mt-0.5">- Rp {{ number_format($this->summary['fee_tunai_hutang'], 0, ',', '.') }}</h3>
                <p class="text-[9px] text-rose-500 italic leading-none mt-1">Wajib disetor fisik</p>
            </div>
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
                <option value="semua">Semua Tipe Kasir</option>
                <option value="digital">💳 Beasiswa (Digital)</option>
                <option value="tunai">💵 Umum (Tunai)</option>
            </select>

            <select wire:model.live="dateRange" class="py-2.5 pl-4 pr-10 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100 cursor-pointer">
                <option value="today">Hari Ini</option>
                <option value="month">Bulan Ini</option>
                <option value="year">Tahun Ini</option>
                <option value="all">Sepanjang Waktu</option>
            </select>
        </div>
    </div>

    {{-- TABEL DATA TRANSAKSI --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead class="bg-gray-50/80 text-gray-400 text-[10px] uppercase font-extrabold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Waktu & Order ID</th>
                        <th class="px-6 py-4">Deskripsi Pesanan</th>
                        <th class="px-6 py-4">Tipe Pembayaran</th>
                        <th class="px-6 py-4 text-right">Volume Transaksi</th>
                        <th class="px-6 py-4 text-right">Status Potongan Fee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->transactions as $trx)
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-gray-900 font-mono">{{ $trx->order_id }}</div>
                                <div class="text-[10px] text-gray-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $trx->created_at->format('d M Y, H:i') }}
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 max-w-xs">
                                <div class="text-xs font-medium text-gray-700 line-clamp-2 leading-relaxed">
                                    {{ str_replace(['[QR] ', '[TUNAI] '], '', $trx->description) }}
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                @if($trx->type === 'pembayaran_makanan_tunai')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-[10px] font-extrabold uppercase tracking-wider border border-amber-200">
                                        💵 Tunai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[10px] font-extrabold uppercase tracking-wider border border-blue-200">
                                        💳 Beasiswa
                                    </span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-extrabold text-gray-900">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</div>
                            </td>
                            
                            <td class="px-6 py-4 text-right">
                                <div class="text-xs font-bold text-rose-500 bg-rose-50 inline-block px-2 py-0.5 rounded border border-rose-100">
                                    -Rp {{ number_format($trx->fee_lkbb, 0, ',', '.') }}
                                </div>
                                @if($trx->type === 'pembayaran_makanan_tunai')
                                    <div class="text-[9px] text-rose-500 mt-1 font-bold">Wajib disetor fisik</div>
                                @else
                                    <div class="text-[9px] text-blue-500 mt-1 font-bold">Lunas (Dipotong dari Digital)</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="text-5xl mb-4 opacity-30 flex justify-center">🧾</div>
                                <h3 class="text-sm font-bold text-gray-600">Tidak ada data transaksi ditemukan.</h3>
                                <p class="text-xs text-gray-400 mt-1">Ubah filter tanggal atau pencarian Anda.</p>
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