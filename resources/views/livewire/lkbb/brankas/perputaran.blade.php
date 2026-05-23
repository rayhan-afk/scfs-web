<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Transaction;
use App\Models\SupplyOrder;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

new 
#[Layout('layouts.lkbb')] 
class extends Component {
    use WithPagination;

    public $search = '';
    public $bulanAktif;

    public function mount()
    {
        $this->bulanAktif = Carbon::now()->format('Y-m');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedBulanAktif() { $this->resetPage(); }

    // 🔥 KUNCI RAHASIA: MENGGABUNGKAN 2 TABEL (TRANSAKSI & PO) MENJADI 1 ALIRAN DATA
    #[Computed]
    public function unifiedData()
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        if (!empty($this->bulanAktif)) {
            $parts = explode('-', $this->bulanAktif);
            if (count($parts) === 2) {
                $year = $parts[0];
                $month = $parts[1];
            }
        }

        // 1. Sedot Data Transaksi (Jual Beli & Injeksi Donasi)
        $txs = Transaction::with(['user', 'merchant'])
            ->whereIn('status', ['sukses', 'lunas'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->map(function($item) {
                return (object) [
                    'id' => 'tx_'.$item->id,
                    'order_id' => $item->order_id,
                    'waktu' => $item->created_at,
                    'subjek' => optional($item->merchant)->name ?? (optional($item->user)->name ?? 'Sistem LKBB Pusat'),
                    'jenis' => $item->type,
                    'deskripsi' => str_replace(['[QR] ', '[TUNAI] '], '', $item->description),
                    'total_pokok' => $item->total_pokok,
                    'fee_lkbb' => $item->fee_lkbb,
                    'total_amount' => $item->total_amount,
                    'is_investasi' => false,
                ];
            });

        // 2. Sedot Data PO (Modal Investasi ke Pemasok)
        $pos = SupplyOrder::with(['merchant', 'pemasok'])
            ->whereIn('status', ['disetujui_lkbb', 'diproses_pemasok', 'dikirim', 'selesai'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->map(function($item) {
                return (object) [
                    'id' => 'po_'.$item->id,
                    'order_id' => $item->nomor_order,
                    'waktu' => $item->created_at,
                    'subjek' => optional($item->pemasok)->name ?? 'Pemasok Terhapus',
                    'jenis' => 'pendanaan_po',
                    'deskripsi' => 'Talangan PO Kantin: ' . (optional($item->merchant)->name ?? '-'),
                    'total_pokok' => $item->total_estimasi, 
                    'fee_lkbb' => 0, // PO belum panen fee
                    'total_amount' => $item->total_estimasi,
                    'is_investasi' => true,
                ];
            });

        // 3. Leburkan Kedua Data & Urutkan Berdasarkan Waktu Terbaru
        $merged = $txs->concat($pos)->sortByDesc('waktu');

        // 4. Fitur Pencarian Dinamis
        if (!empty($this->search)) {
            $search = strtolower(trim($this->search));
            $merged = $merged->filter(function($item) use ($search) {
                return str_contains(strtolower($item->order_id), $search) 
                    || str_contains(strtolower($item->subjek), $search)
                    || str_contains(strtolower($item->deskripsi), $search);
            });
        }

        return $merged;
    }

    #[Computed]
    public function ringkasan()
    {
        $data = $this->unifiedData;
        
        return [
            'total_gmv'       => $data->sum('total_amount'),
            'total_pokok'     => $data->sum('total_pokok'),
            'total_fee'       => $data->sum('fee_lkbb'),
            'total_transaksi' => $data->count(),
        ];
    }

    // Paginate Manual untuk Collection Gabungan
    #[Computed]
    public function logs()
    {
        $data = $this->unifiedData;
        $page = request()->get('page', 1);
        $perPage = 15;
        
        return new LengthAwarePaginator(
            $data->forPage($page, $perPage),
            $data->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}; ?>

<div class="p-6">
    {{-- HEADER --}}
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('lkbb.dashboard') }}" class="text-sm font-bold text-gray-400 hover:text-purple-600 transition">Dashboard</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-purple-600">Audit Brankas</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                <span class="text-purple-500">🟣</span> Audit Volume & Perputaran (GMV)
            </h1>
            <p class="text-gray-500 text-sm mt-1">Rekapitulasi total omzet kotor, pendanaan PO, perputaran HPP, dan laba bersih secara makro.</p>
        </div>
        
        <button class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl transition shadow-sm flex items-center gap-2 hover:bg-gray-50 group">
            <svg class="w-4 h-4 text-purple-500 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export Rekap Makro
        </button>
    </div>

    {{-- HIGHLIGHT CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Card 1: Gross Merchandise Value (GMV) --}}
        <div class="bg-gradient-to-br from-purple-600 to-indigo-800 rounded-2xl p-5 text-white shadow-lg shadow-purple-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-6 -mt-6"></div>
            <p class="text-purple-100 text-[10px] font-extrabold uppercase tracking-wider mb-1">Total Nilai Perputaran (GMV)</p>
            <h3 class="text-2xl font-black tracking-tight mt-1">Rp {{ number_format($this->ringkasan['total_gmv'], 0, ',', '.') }}</h3>
            <p class="text-[10px] text-purple-200 mt-2 font-medium bg-purple-900/30 w-fit px-2 py-1 rounded">Semua Aliran Uang Berputar</p>
        </div>

        {{-- Card 2: Perputaran Pokok HPP --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-sky-500">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Sirkulasi Nilai Modal (HPP)</p>
             <h3 class="text-xl font-black text-gray-900">Rp {{ number_format($this->ringkasan['total_pokok'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-sky-600 font-bold mt-1">Aset modal barang terputar</p>
        </div>

        {{-- Card 3: Total Laba Bersih LKBB --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-emerald-500">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Laba Bersih LKBB (Total Profit)</p>
             <h3 class="text-xl font-black text-emerald-600">Rp {{ number_format($this->ringkasan['total_fee'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-emerald-600 font-bold mt-1">Akumulasi keuntungan murni</p>
        </div>

        {{-- Card 4: Volume Transaksi Gabungan --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center bg-gray-50/50">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Frekuensi Aktivitas</p>
             <h3 class="text-xl font-black text-gray-900">{{ number_format($this->ringkasan['total_transaksi'], 0, ',', '.') }} <span class="text-xs text-gray-400 font-bold">Aktivitas</span></h3>
             <p class="text-[10px] text-gray-500 font-bold mt-1">Penjualan, Injeksi & Pendanaan PO</p>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col lg:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Cari berdasarkan Ref ID atau subjek pelaku..." 
                class="w-full py-2.5 pl-11 pr-4 text-sm font-medium text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-purple-500 focus:bg-white focus:ring-2 focus:ring-purple-200 transition">
        </div>

        <div class="w-full lg:w-64">
            <input wire:model.live="bulanAktif" type="month" 
                class="w-full py-2.5 px-4 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-purple-500 focus:bg-white focus:ring-2 focus:ring-purple-200 cursor-pointer">
        </div>
    </div>

    {{-- TABEL AUDIT LOGS --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead class="bg-purple-50/50 border-b border-purple-100">
                    <tr class="text-[10px] text-purple-800 uppercase tracking-widest font-black">
                        <th class="px-5 py-4">Waktu & Ref. ID</th>
                        <th class="px-5 py-4">Subjek Transaksi</th>
                        <th class="px-5 py-4">Jenis Aktivitas</th>
                        <th class="px-5 py-4 text-right">Nilai Pokok (HPP)</th>
                        <th class="px-5 py-4 text-right">Laba LKBB</th>
                        <th class="px-5 py-4 text-right">Volume Berputar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->logs as $log)
                        <tr class="hover:bg-gray-50/50 transition group">
                            {{-- Kolom 1: ID --}}
                            <td class="px-5 py-4">
                                <div class="text-xs font-black text-gray-900 font-mono">{{ $log->order_id }}</div>
                                <div class="text-[10px] text-gray-400 mt-1">{{ \Carbon\Carbon::parse($log->waktu)->format('d M y - H:i') }}</div>
                            </td>
                            
                            {{-- Kolom 2: Subjek (Dinamis: Pemasok, LKBB, atau Kantin) --}}
                            <td class="px-5 py-4">
                                <div class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                    @if($log->is_investasi)
                                        🏢 {{ $log->subjek }}
                                    @elseif(str_contains(strtolower($log->subjek), 'pusat'))
                                        🏦 {{ $log->subjek }}
                                    @else
                                        🏪 {{ $log->subjek }}
                                    @endif
                                </div>
                                <div class="text-[9px] text-gray-500 mt-1 max-w-[200px] truncate" title="{{ $log->deskripsi }}">
                                    {{ $log->deskripsi }}
                                </div>
                            </td>

                            {{-- Kolom 3: Jenis Aktivitas (Sangat Dinamis) --}}
                            <td class="px-5 py-4">
                                @if($log->jenis === 'pendanaan_po')
                                    <span class="inline-flex px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[9px] font-extrabold uppercase border border-indigo-200">📦 Modal PO Pemasok</span>
                                @elseif($log->jenis === 'pembayaran_makanan_tunai')
                                    <span class="inline-flex px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-[9px] font-extrabold uppercase border border-amber-200">💵 Jual Beli Tunai</span>
                                @elseif($log->jenis === 'pembayaran_makanan')
                                    <span class="inline-flex px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[9px] font-extrabold uppercase border border-blue-200">💳 Jual Beli Digital</span>
                                @elseif(str_contains(strtolower($log->jenis), 'injeksi') || str_contains(strtolower($log->jenis), 'topup'))
                                    <span class="inline-flex px-2 py-0.5 rounded bg-purple-50 text-purple-700 text-[9px] font-extrabold uppercase border border-purple-200">💰 Injeksi Dana</span>
                                @elseif(str_contains(strtolower($log->jenis), 'tarik') || str_contains(strtolower($log->jenis), 'withdraw'))
                                    <span class="inline-flex px-2 py-0.5 rounded bg-rose-50 text-rose-700 text-[9px] font-extrabold uppercase border border-rose-200">🏦 Tarik Tunai</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-[9px] font-extrabold uppercase border border-gray-200">🔄 {{ str_replace('_', ' ', $log->jenis) }}</span>
                                @endif
                            </td>

                            {{-- Kolom 4: HPP --}}
                            <td class="px-5 py-4 text-right font-bold text-gray-600 text-xs">
                                Rp {{ number_format($log->total_pokok, 0, ',', '.') }}
                            </td>

                            {{-- Kolom 5: Laba LKBB --}}
                            <td class="px-5 py-4 text-right font-bold text-emerald-600 text-xs">
                                @if($log->fee_lkbb > 0)
                                    + Rp {{ number_format($log->fee_lkbb, 0, ',', '.') }}
                                @else
                                    Rp 0
                                @endif
                            </td>

                            {{-- Kolom 6: Gross --}}
                            <td class="px-5 py-4 text-right">
                                <span class="text-sm font-black text-gray-900 bg-gray-50 border border-gray-100 px-2.5 py-1 rounded-lg">
                                    Rp {{ number_format($log->total_amount, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-24 text-center">
                                <div class="text-5xl mb-4 opacity-20 flex justify-center">📊</div>
                                <h3 class="text-sm font-bold text-gray-600">Belum ada aktivitas perputaran di bulan ini.</h3>
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