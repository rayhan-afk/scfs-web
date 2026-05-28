<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\SupplyOrder;
use App\Models\Wallet;
use Carbon\Carbon;

new 
#[Layout('layouts.lkbb')] 
class extends Component {
    use WithPagination;

    public $search = '';
    public $bulanAktif;

    public function mount()
    {
        // Set default filter ke bulan ini
        $this->bulanAktif = Carbon::now()->format('Y-m');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedBulanAktif() { $this->resetPage(); }

    // 1. Ambil Saldo Terkini Dompet Investasi
    #[Computed]
    public function dompetTerkini()
    {
        $wallet = Wallet::where('type', 'LKBB_INVESTMENT')->first();
        return $wallet ? $wallet->balance : 0;
    }

    // 2. Query Utama: Menarik data pendanaan PO (Uang Keluar ke Pemasok)
    // BUGFIX: status enum supply_orders TIDAK punya value 'disetujui_lkbb'.
    // PO yang sudah didanai LKBB ditandai oleh kolom dedicated `status_pembiayaan='didanai'`
    // (di-set oleh ApprovalPo.php saat LKBB approve pencairan modal).
    #[Computed]
    public function baseQuery()
    {
        $query = SupplyOrder::with(['merchant.merchantProfile', 'pemasok.pemasokProfile'])
            ->where('status_pembiayaan', 'didanai');

        if (!empty($this->search)) {
            $query->where('nomor_order', 'like', '%' . trim($this->search) . '%');
        }

        if (!empty($this->bulanAktif)) {
            $parts = explode('-', $this->bulanAktif);
            if (count($parts) === 2) {
                $query->whereYear('created_at', $parts[0])
                      ->whereMonth('created_at', $parts[1]);
            }
        }

        return $query;
    }

    // 3. Rekapitulasi Aliran Modal
    #[Computed]
    public function ringkasan()
    {
        return [
            // Ganti 'total_estimasi' sesuai dengan nama kolom total harga PO di database Anda
            'total_pendanaan_keluar' => (clone $this->baseQuery)->sum('total_estimasi'),
            'jumlah_po_didanai'      => (clone $this->baseQuery)->count(),
        ];
    }

    // 4. Data Tabel (Paginated)
    #[Computed]
    public function logs()
    {
        return (clone $this->baseQuery)->latest()->paginate(15);
    }
}; ?>

<div class="p-6">
    {{-- HEADER --}}
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('lkbb.dashboard') }}" class="text-sm font-bold text-gray-400 hover:text-indigo-600 transition">Dashboard</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-indigo-600">Audit Brankas</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                <span class="text-indigo-500">🔵</span> Log Alokasi Modal (Investasi)
            </h1>
            <p class="text-gray-500 text-sm mt-1">Audit aliran dana keluar untuk pendanaan (talangan) pesanan barang Merchant ke Pemasok.</p>
        </div>
        
        <button class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl transition shadow-sm flex items-center gap-2 hover:bg-gray-50 group">
            <svg class="w-4 h-4 text-indigo-500 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export Laporan
        </button>
    </div>

    {{-- HIGHLIGHT CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        {{-- Card 1: Saldo Terkini (Sisa Modal) — admin gradient pattern --}}
        <div class="bg-gradient-to-br from-indigo-600 to-blue-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10 pointer-events-none transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 7h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-[9px] font-bold tracking-widest uppercase">Brankas</span>
                </div>
                <p class="text-indigo-200 text-[10px] font-bold tracking-wider mb-1 uppercase">Sisa Brankas Investasi</p>
                <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md">Rp {{ number_format($this->dompetTerkini, 0, ',', '.') }}</h3>
                <p class="text-[10px] text-indigo-100 mt-3 font-medium inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-indigo-300 rounded-full animate-pulse"></span> Dana siap disuntikkan ke Pemasok
                </p>
            </div>
        </div>

        {{-- Card 2: Total Pendanaan Keluar --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-rose-500 relative overflow-hidden">
             <div class="absolute right-0 bottom-0 opacity-5 mb-2 mr-2"><svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11h5l-9 10-9-10h5v-9h8v9zm-8-5h-4l6-7 6 7h-4v7h-4v-7z"/></svg></div>
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Total Modal Disalurkan (Bulan Ini)</p>
             <h3 class="text-2xl font-black text-gray-900">Rp {{ number_format($this->ringkasan['total_pendanaan_keluar'], 0, ',', '.') }}</h3>
             <p class="text-[11px] text-rose-500 font-bold mt-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                Uang keluar untuk bayar Pemasok
             </p>
        </div>

        {{-- Card 3: Jumlah PO Didanai --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-sky-500 bg-slate-50/50">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Volume Penyaluran</p>
             <h3 class="text-2xl font-black text-sky-600">{{ number_format($this->ringkasan['jumlah_po_didanai'], 0, ',', '.') }} <span class="text-sm font-bold text-gray-500">PO Merchant</span></h3>
             <p class="text-[11px] text-gray-500 font-bold mt-2">Telah disetujui & dibayarkan</p>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col lg:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Cari berdasarkan Nomor PO..." 
                class="w-full py-2.5 pl-11 pr-4 text-sm font-medium text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-200 transition">
        </div>

        <div class="w-full lg:w-64">
            <input wire:model.live="bulanAktif" type="month" 
                class="w-full py-2.5 px-4 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-200 cursor-pointer">
        </div>
    </div>

    {{-- TABEL AUDIT LOGS --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead class="bg-indigo-50/50 border-b border-indigo-100">
                    <tr class="text-[10px] text-indigo-800 uppercase tracking-widest font-black">
                        <th class="px-5 py-4">Ref. Pendanaan</th>
                        <th class="px-5 py-4">Tujuan (Pemasok)</th>
                        <th class="px-5 py-4">Penerima Manfaat (Kantin)</th>
                        <th class="px-5 py-4 text-center">Status Barang</th>
                        <th class="px-5 py-4 text-right">Nominal Disalurkan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->logs as $log)
                        <tr class="hover:bg-gray-50/50 transition group">
                            {{-- Kolom 1: Waktu & Ref --}}
                            <td class="px-5 py-4">
                                <div class="text-xs font-black text-gray-900 font-mono">{{ $log->nomor_order }}</div>
                                <div class="text-[10px] text-gray-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ $log->created_at->format('d M y, H:i') }}
                                </div>
                            </td>
                            
                            {{-- Kolom 2: Pemasok (Yang dibayar) — link ke buku besar pemasok --}}
                            <td class="px-5 py-4">
                                <div class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                                    @if($log->pemasok)
                                        <a href="{{ route('lkbb.entitas.pemasok-detail', $log->pemasok->id) }}" wire:navigate
                                           class="hover:text-indigo-600 hover:underline transition truncate">
                                            {{ $log->pemasok->pemasokProfile->nama_perusahaan ?? $log->pemasok->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 italic">Pemasok Terhapus</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-gray-500 mt-1 ml-4">Vendor Barang</div>
                            </td>

                            {{-- Kolom 3: Kantin — link ke buku besar merchant --}}
                            <td class="px-5 py-4">
                                @if($log->merchant)
                                    <a href="{{ route('lkbb.entitas.merchant-detail', $log->merchant->id) }}" wire:navigate
                                       class="text-sm font-bold text-gray-700 hover:text-emerald-600 hover:underline transition">
                                        {{ $log->merchant->merchantProfile->nama_kantin ?? $log->merchant->name }}
                                    </a>
                                @else
                                    <span class="text-sm font-bold text-gray-400 italic">Kantin Terhapus</span>
                                @endif
                                <div class="text-[10px] text-gray-500 mt-1">Pemohon PO</div>
                            </td>

                            {{-- Kolom 4: Status --}}
                            <td class="px-5 py-4 text-center">
                                @php
                                    $statusStyle = match($log->status) {
                                        'diproses_pemasok' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'dikirim' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200'
                                    };
                                @endphp
                                <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded border {{ $statusStyle }}">
                                    {{ str_replace('_', ' ', $log->status) }}
                                </span>
                            </td>

                            {{-- Kolom 5: Nominal --}}
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex flex-col items-end">
                                    <span class="text-sm font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100">
                                        - Rp {{ number_format($log->total_estimasi, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 font-bold mt-1">Modal Dipotong</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-24 text-center">
                                <div class="text-5xl mb-4 opacity-20 flex justify-center">💼</div>
                                <h3 class="text-sm font-bold text-gray-600">Belum ada penyaluran modal bulan ini.</h3>
                                <p class="text-xs text-gray-400 mt-1">Semua pesanan (PO) merchant yang didanai akan muncul di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $this->logs->links() }}
            </div>
        @endif
    </div>
</div>