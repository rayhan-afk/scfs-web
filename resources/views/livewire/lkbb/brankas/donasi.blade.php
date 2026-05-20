<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Transaction;
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
        // Default filter ke bulan ini
        $this->bulanAktif = Carbon::now()->format('Y-m');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedBulanAktif() { $this->resetPage(); }

    // 1. Ambil Saldo Brankas Donasi Saat Ini
    #[Computed]
    public function dompetTerkini()
    {
        $wallet = Wallet::where('type', 'LKBB_DONATION')->first();
        return $wallet ? $wallet->balance : 0;
    }

    // 2. Query Utama: Murni mengambil transaksi INJEKSI/BANTUAN dari LKBB ke Mahasiswa
    #[Computed]
    public function baseQuery()
    {
        $query = Transaction::with(['user'])
            ->whereIn('status', ['sukses', 'lunas'])
            ->whereNull('merchant_id') // KUNCI: Transaksi dari LKBB tidak lewat kantin
            ->whereNotNull('user_id')  // Harus ada mahasiswa penerimanya
            ->where(function($q) {
                // Kecualikan transaksi penarikan uang (withdraw)
                $q->where('type', 'not like', '%withdraw%')
                  ->where('type', 'not like', '%tarik%');
            });

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('order_id', 'like', '%' . trim($this->search) . '%')
                  ->orWhereHas('user', function($u) {
                      $u->where('name', 'like', '%' . trim($this->search) . '%');
                  });
            });
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

    // 3. Agregasi Penyaluran Dana
    #[Computed]
    public function ringkasan()
    {
        $allTrx = (clone $this->baseQuery)->get();
        
        return [
            'total_disalurkan'     => $allTrx->sum('total_amount'),
            'jumlah_injeksi'       => $allTrx->count(),
            'mahasiswa_penerima'   => $allTrx->unique('user_id')->count(),
        ];
    }

    // 4. Get Data untuk Tabel (Paginated)
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
                <a href="{{ route('lkbb.dashboard') }}" class="text-sm font-bold text-gray-400 hover:text-amber-600 transition">Dashboard</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-amber-600">Audit Brankas</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                <span class="text-orange-500">🟡</span> Log Penyaluran Beasiswa (Donasi)
            </h1>
            <p class="text-gray-500 text-sm mt-1">Pantau riwayat injeksi dan penyaluran dana bantuan dari LKBB kepada mahasiswa.</p>
        </div>
        
        <button class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl transition shadow-sm flex items-center gap-2 hover:bg-gray-50 group">
            <svg class="w-4 h-4 text-orange-500 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Cetak Log Penyaluran
        </button>
    </div>

    {{-- HIGHLIGHT CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        {{-- Card 1: Saldo Donasi Siap Salur --}}
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg shadow-orange-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
            <p class="text-orange-100 text-[10px] font-extrabold uppercase tracking-widest mb-1">Sisa Saldo Brankas Donasi</p>
            <h3 class="text-3xl font-black tracking-tight mt-1">Rp {{ number_format($this->dompetTerkini, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-orange-50 mt-3 font-medium bg-orange-900/30 w-fit px-2.5 py-1 rounded-md inline-flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 bg-amber-300 rounded-full animate-ping"></span> Dana Siap Disalurkan Kembali
            </p>
        </div>

        {{-- Card 2: Total Beasiswa Disalurkan --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-orange-500 relative overflow-hidden">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Total Dana Disalurkan (Bulan Ini)</p>
             <h3 class="text-2xl font-black text-gray-900">Rp {{ number_format($this->ringkasan['total_disalurkan'], 0, ',', '.') }}</h3>
             <p class="text-[11px] text-orange-600 font-bold mt-2 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                Telah diinjeksi ke E-Wallet Mahasiswa
             </p>
        </div>

        {{-- Card 3: Mahasiswa Penerima --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-sky-500 bg-slate-50/50">
             <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Penerima Manfaat Baru</p>
             <h3 class="text-2xl font-black text-sky-600">{{ number_format($this->ringkasan['mahasiswa_penerima'], 0, ',', '.') }} <span class="text-sm font-bold text-gray-500">Mahasiswa</span></h3>
             <p class="text-[11px] text-gray-500 font-bold mt-2">Dari total {{ number_format($this->ringkasan['jumlah_injeksi'], 0, ',', '.') }} kali penyaluran</p>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col lg:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Cari berdasarkan Ref ID atau Nama Mahasiswa..." 
                class="w-full py-2.5 pl-11 pr-4 text-sm font-medium text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-orange-500 focus:bg-white focus:ring-2 focus:ring-orange-200 transition">
        </div>

        <div class="w-full lg:w-64">
            <input wire:model.live="bulanAktif" type="month" 
                class="w-full py-2.5 px-4 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-orange-500 focus:bg-white focus:ring-2 focus:ring-orange-200 cursor-pointer">
        </div>
    </div>

    {{-- TABEL AUDIT LOGS --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead class="bg-orange-50/50 border-b border-orange-100">
                    <tr class="text-[10px] text-orange-800 uppercase tracking-widest font-black">
                        <th class="px-5 py-4">Ref. ID & Waktu</th>
                        <th class="px-5 py-4">Mahasiswa Penerima</th>
                        <th class="px-5 py-4">Jenis Penyaluran</th>
                        <th class="px-5 py-4">Keterangan</th>
                        <th class="px-5 py-4 text-right">Nominal Bantuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->logs as $log)
                        <tr class="hover:bg-gray-50/50 transition group">
                            {{-- Kolom 1: ID & Waktu --}}
                            <td class="px-5 py-4">
                                <div class="text-xs font-black text-gray-900 font-mono">{{ $log->order_id }}</div>
                                <div class="text-[10px] text-gray-400 mt-1">{{ $log->created_at->format('d M y - H:i') }}</div>
                            </td>
                            
                            {{-- Kolom 2: Nama Mahasiswa --}}
                            <td class="px-5 py-4">
                                <div class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-orange-100 text-orange-700 text-[10px] flex items-center justify-center font-black">
                                        🎓
                                    </div>
                                    {{ optional($log->user)->name ?? 'Mahasiswa Terhapus' }}
                                </div>
                                <div class="text-[9px] font-mono text-gray-400 mt-0.5 ml-8">UID: #{{ $log->user_id }}</div>
                            </td>

                            {{-- Kolom 3: Jenis Transaksi --}}
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[9px] font-extrabold uppercase border border-blue-100">
                                    💰 Injeksi Sistem
                                </span>
                            </td>

                            {{-- Kolom 4: Keterangan (Dari Database) --}}
                            <td class="px-5 py-4 max-w-[200px]">
                                <div class="text-xs font-medium text-gray-600 line-clamp-2" title="{{ $log->description }}">
                                    {{ $log->description ?? 'Penyaluran Bantuan Rutin' }}
                                </div>
                            </td>

                            {{-- Kolom 5: Nominal Disalurkan --}}
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex flex-col items-end">
                                    <span class="text-sm font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-lg shadow-sm">
                                        + Rp {{ number_format($log->total_amount, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 font-bold mt-1">Masuk ke E-Wallet Mhs</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-24 text-center">
                                <div class="text-5xl mb-4 opacity-20 flex justify-center">🎁</div>
                                <h3 class="text-sm font-bold text-gray-600">Belum ada penyaluran beasiswa di bulan ini.</h3>
                                <p class="text-xs text-gray-400 mt-1">Setiap kali LKBB menginjeksi saldo ke E-Wallet mahasiswa, datanya akan teraudit di sini.</p>
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