<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new 
#[Layout('layouts.lkbb')] 
class extends Component {
    use WithPagination;

    public $search = '';
    public $bulanAktif;

    public function mount()
    {
        // Set default filter ke bulan ini (Format YYYY-MM)
        $this->bulanAktif = Carbon::now()->format('Y-m');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedBulanAktif() { $this->resetPage(); }

    // 1. Ambil Saldo Terkini Dompet Operasional
    #[Computed]
    public function dompetTerkini()
    {
        $wallet = Wallet::where('type', 'LKBB_OPERATIONAL')->first();
        return $wallet ? $wallet->balance : 0;
    }

    // 2. Query Utama (Hanya hitung transaksi yang sukses/lunas)
    #[Computed]
    public function baseQuery()
    {
        $query = Transaction::with('merchant')
            ->whereIn('status', ['sukses', 'lunas'])
            ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai']);

        if (!empty($this->search)) {
            $query->where('order_id', 'like', '%' . trim($this->search) . '%');
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

    // 3. Rekapitulasi (Modal + Fee) sesuai filter
    #[Computed]
    public function ringkasan()
    {
        return [
            'total_pokok_kembali' => (clone $this->baseQuery)->sum('total_pokok'),
            'total_laba_kotor'    => (clone $this->baseQuery)->sum('fee_lkbb'),
            'total_aliran_masuk'  => (clone $this->baseQuery)->sum(DB::raw('total_pokok + fee_lkbb')),
            'jumlah_transaksi'    => (clone $this->baseQuery)->count(),
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
                <a href="{{ route('lkbb.dashboard') }}" class="text-sm font-bold text-gray-400 hover:text-emerald-600 transition">Dashboard</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-emerald-600">Audit Brankas</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                <span class="text-emerald-500">🟢</span> Log Sirkulasi (Operasional)
            </h1>
            <p class="text-gray-500 text-sm mt-1">Audit aliran masuk pengembalian Modal Kantin (HPP) dan Potongan Fee LKBB.</p>
        </div>
        
        <button class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl transition shadow-sm flex items-center gap-2 hover:bg-gray-50 group">
            <svg class="w-4 h-4 text-emerald-500 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export Excel
        </button>
    </div>

    {{-- HIGHLIGHT CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Card 1: Saldo Terkini (Real-time Wallet) --}}
        <div class="bg-gradient-to-br from-emerald-600 to-green-800 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-6 -mt-6"></div>
            <p class="text-emerald-100 text-[10px] font-extrabold uppercase tracking-wider mb-1">Saldo Brankas Terkini</p>
            <h3 class="text-2xl font-black tracking-tight mt-1">Rp {{ number_format($this->dompetTerkini, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-emerald-200 mt-2 font-medium bg-emerald-900/30 w-fit px-2 py-1 rounded inline-flex items-center gap-1">
                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span> Sinkronisasi Real-time
            </p>
        </div>

        {{-- Card 2: Pengembalian Modal --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-sky-500">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Total Modal Kembali</p>
             <h3 class="text-xl font-black text-gray-900">Rp {{ number_format($this->ringkasan['total_pokok_kembali'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-sky-600 font-bold mt-1">Uang modal barang yang laku</p>
        </div>

        {{-- Card 3: Fee Laba LKBB --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-purple-500">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Total Keuntungan (Fee)</p>
             <h3 class="text-xl font-black text-gray-900">Rp {{ number_format($this->ringkasan['total_laba_kotor'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-purple-600 font-bold mt-1">Potongan bagi hasil dari kantin</p>
        </div>

        {{-- Card 4: Total Aliran Masuk --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center bg-gray-50/50">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Total Uang Masuk</p>
             <h3 class="text-xl font-black text-emerald-600">Rp {{ number_format($this->ringkasan['total_aliran_masuk'], 0, ',', '.') }}</h3>
             <p class="text-[10px] text-gray-500 font-bold mt-1">Dari {{ number_format($this->ringkasan['jumlah_transaksi'], 0, ',', '.') }} Transaksi Sukses</p>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col lg:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Cari berdasarkan Order ID..." 
                class="w-full py-2.5 pl-11 pr-4 text-sm font-medium text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-200 transition">
        </div>

        <div class="w-full lg:w-64">
            <input wire:model.live="bulanAktif" type="month" 
                class="w-full py-2.5 px-4 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-200 cursor-pointer">
        </div>
    </div>

    {{-- TABEL AUDIT LOGS --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-[10px] text-gray-500 uppercase tracking-widest font-black">
                        <th class="px-5 py-4">Waktu & Ref</th>
                        <th class="px-5 py-4">Sumber Kantin</th>
                        <th class="px-5 py-4 text-right">Modal (HPP)</th>
                        <th class="px-5 py-4 text-right">Keuntungan (Fee)</th>
                        <th class="px-5 py-4 text-right">Total Masuk Brankas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->logs as $log)
                        <tr class="hover:bg-gray-50/50 transition group">
                            {{-- Kolom 1: Waktu --}}
                            <td class="px-5 py-4">
                                <div class="text-xs font-bold text-gray-900 font-mono">{{ $log->order_id }}</div>
                                <div class="text-[10px] text-gray-500 mt-0.5">{{ $log->created_at->format('d M Y, H:i') }}</div>
                                @if($log->type === 'pembayaran_makanan_tunai')
                                    <span class="inline-block mt-1.5 text-[8px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded font-bold uppercase border border-amber-100">Tagihan Setoran Fisik</span>
                                @else
                                    <span class="inline-block mt-1.5 text-[8px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded font-bold uppercase border border-blue-100">Saldo Digital Masuk</span>
                                @endif
                            </td>
                            
                            {{-- Kolom 2: Kantin --}}
                            <td class="px-5 py-4">
                                <div class="text-sm font-bold text-gray-800">{{ optional($log->merchant)->name ?? 'Kantin Terhapus' }}</div>
                                <div class="text-[10px] text-gray-500 mt-0.5 truncate max-w-[200px]" title="{{ str_replace(['[QR] ', '[TUNAI] '], '', $log->description) }}">
                                    Item: {{ str_replace(['[QR] ', '[TUNAI] '], '', $log->description) }}
                                </div>
                            </td>

                            {{-- Kolom 3: Modal --}}
                            <td class="px-5 py-4 text-right">
                                <span class="text-sm font-black text-gray-600">Rp {{ number_format($log->total_pokok, 0, ',', '.') }}</span>
                            </td>

                            {{-- Kolom 4: Fee --}}
                            <td class="px-5 py-4 text-right">
                                <span class="text-sm font-black text-purple-600">Rp {{ number_format($log->fee_lkbb, 0, ',', '.') }}</span>
                            </td>

                            {{-- Kolom 5: Total Masuk --}}
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                                    <span class="text-sm font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-lg">
                                        Rp {{ number_format($log->total_pokok + $log->fee_lkbb, 0, ',', '.') }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-24 text-center">
                                <div class="text-5xl mb-4 opacity-20 flex justify-center">🗄️</div>
                                <h3 class="text-sm font-bold text-gray-600">Tidak ada sirkulasi operasional di bulan ini.</h3>
                                <p class="text-xs text-gray-400 mt-1">Ubah filter bulan atau lakukan transaksi di POS Kantin.</p>
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