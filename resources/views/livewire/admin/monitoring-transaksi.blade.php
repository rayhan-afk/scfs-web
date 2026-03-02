<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Transaction;
use App\Models\User;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $search = '';
    public $filterKategori = 'Semua';
    public $filterTipe = 'Semua';

    public function getTransactionsProperty()
    {
        $query = Transaction::with(['user', 'relatedUser'])->latest();

        if ($this->filterKategori !== 'Semua') {
            $query->where('category', $this->filterKategori);
        }

        if ($this->filterTipe !== 'Semua') {
            $query->where('type', strtolower($this->filterTipe));
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function($userQuery) {
                      $userQuery->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('relatedUser', function($relQuery) {
                      $relQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->limit(100)->get(); // Limit 100 terbaru agar tidak berat
    }

    public function getStatsProperty()
    {
        return [
            'total_volume' => Transaction::where('status', 'success')->sum('amount'),
            'total_count' => Transaction::count(),
            'total_pending' => Transaction::where('status', 'pending')->count(),
        ];
    }

    // ==========================================
    // FUNGSI TESTING: BIKIN TRANSAKSI DUMMY
    // ==========================================
    public function buatTransaksiDummy()
    {
        // Mencari sembarang user untuk dijadikan contoh (pastikan ada minimal 1 user di DB)
        $user = User::first(); 
        
        if($user) {
            Transaction::create([
                'reference_number' => 'TRX-' . date('ymd') . '-' . rand(1000, 9999),
                'user_id' => $user->id,
                'type' => 'kredit', // kredit atau debit
                'category' => 'donasi_masuk', 
                'amount' => rand(50000, 500000),
                'description' => 'Testing transaksi masuk dari sistem',
                'status' => 'success'
            ]);
        }
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Monitoring Transaksi</h2>
            <p class="text-gray-500 text-sm mt-1">Pantau seluruh pergerakan arus kas dan token di dalam ekosistem SCFS.</p>
        </div>
        
        <button wire:click="buatTransaksiDummy" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-lg shadow-sm transition">
            + Generate Transaksi Dummy
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Volume Transaksi (Sukses)</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_volume'], 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Total Aktivitas Tercatat</p>
                <h3 class="text-xl font-extrabold text-gray-900">{{ number_format($this->stats['total_count'], 0, ',', '.') }} <span class="text-xs font-medium text-gray-500">Trx</span></h3>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl {{ $this->stats['total_pending'] > 0 ? 'bg-orange-50 text-orange-600 animate-pulse' : 'bg-gray-50 text-gray-400' }} flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Menunggu Diproses (Pending)</p>
                <h3 class="text-xl font-extrabold {{ $this->stats['total_pending'] > 0 ? 'text-orange-600' : 'text-gray-900' }}">{{ $this->stats['total_pending'] }} <span class="text-xs font-medium text-gray-500">Trx</span></h3>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex flex-col md:flex-row gap-3 w-full flex-1">
            
            <div class="relative w-full md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari No. Resi atau Nama User..." 
                    class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 transition">
            </div>

            <div class="relative w-full md:w-48">
                <select wire:model.live="filterKategori" class="appearance-none w-full py-2.5 pl-4 pr-10 text-sm font-medium text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition">
                    <option value="Semua">Semua Kategori</option>
                    <option value="donasi_masuk">Donasi Masuk</option>
                    <option value="beli_makan">Beli Makan (Token)</option>
                    <option value="pencairan_merchant">Pencairan Kantin</option>
                    <option value="bayar_po">Pembayaran PO</option>
                    <option value="bagi_hasil">Bagi Hasil Investor</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            <div class="relative w-full md:w-40">
                <select wire:model.live="filterTipe" class="appearance-none w-full py-2.5 pl-4 pr-10 text-sm font-medium text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition">
                    <option value="Semua">Semua Arus</option>
                    <option value="Kredit">Uang Masuk (Kredit)</option>
                    <option value="Debit">Uang Keluar (Debit)</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

        </div>
        
        <button wire:click="$refresh" class="w-full md:w-auto px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-100 font-medium text-sm transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            Refresh
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">No. Ref & Waktu</th>
                        <th class="px-6 py-4">Pelaku Utama</th>
                        <th class="px-6 py-4">Pihak Terkait (Tujuan)</th>
                        <th class="px-6 py-4">Kategori Mutasi</th>
                        <th class="px-6 py-4 text-right">Nominal Uang/Token</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->transactions as $trx)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 text-xs font-mono">{{ $trx->reference_number }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">{{ $trx->created_at->format('d M Y, H:i') }}</div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-800">{{ $trx->user->name ?? 'Sistem' }}</div>
                            <div class="text-[10px] text-gray-500 uppercase tracking-wider">{{ $trx->user->role ?? 'Admin' }}</div>
                        </td>

                        <td class="px-6 py-4">
                            @if($trx->relatedUser)
                                <div class="text-sm font-bold text-gray-700">{{ $trx->relatedUser->name }}</div>
                                <div class="text-[10px] text-gray-500 uppercase tracking-wider">{{ $trx->relatedUser->role }}</div>
                            @else
                                <span class="text-gray-400 text-xs italic">-</span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <span class="bg-gray-100 text-gray-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-gray-200">
                                {{ str_replace('_', ' ', $trx->category) }}
                            </span>
                            @if($trx->description)
                                <p class="text-[10px] text-gray-500 mt-1.5 w-40 truncate" title="{{ $trx->description }}">{{ $trx->description }}</p>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            @if($trx->type == 'kredit')
                                <div class="text-sm font-bold text-green-600">+ Rp {{ number_format($trx->amount, 0, ',', '.') }}</div>
                            @else
                                <div class="text-sm font-bold text-red-500">- Rp {{ number_format($trx->amount, 0, ',', '.') }}</div>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($trx->status == 'success')
                                <span class="bg-green-50 text-green-600 text-[10px] px-2 py-0.5 rounded font-bold uppercase">Sukses</span>
                            @elseif($trx->status == 'pending')
                                <span class="bg-orange-50 text-orange-600 text-[10px] px-2 py-0.5 rounded font-bold uppercase">Pending</span>
                            @else
                                <span class="bg-red-50 text-red-600 text-[10px] px-2 py-0.5 rounded font-bold uppercase">Gagal</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <p class="text-gray-500 text-sm font-medium">Belum ada riwayat transaksi yang tercatat.</p>
                            <p class="text-gray-400 text-[10px] mt-1">Gunakan tombol "Generate Transaksi Dummy" di atas untuk mencoba.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>