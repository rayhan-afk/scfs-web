<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Transaction;
use App\Models\Wallet;       // <--- Import Wallet
use App\Models\LedgerEntry;  // <--- Import Ledger
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

new 
#[Layout('layouts.lkbb')] 
class extends Component {

    public function with()
    {
        // ---------------------------------------------------------
        // 1. DATA KARTU (REAL-TIME BALANCE DARI WALLET)
        // ---------------------------------------------------------
        
        // A. MODAL KERJA (Saldo Real Dompet LKBB)
        // Kita cari wallet dengan tipe 'LKBB_MASTER'
        $lkbbWallet = Wallet::where('type', 'LKBB_MASTER')->first();
        $modalKerja = $lkbbWallet ? $lkbbWallet->balance : 0;

        // B. SALDO DONASI TERSEDIA
        // Asumsi: Ada wallet khusus penampung donasi ('DONATION_POOL')
        // Jika belum ada, kita hitung dari sisa transaksi donasi yg belum terpakai
        $walletDonasi = Wallet::where('type', 'DONATION_POOL')->first();
        $saldoDonasi = $walletDonasi 
            ? $walletDonasi->balance 
            : Transaction::where('type', 'donation')->where('status', 'lunas')->sum('total_amount');

        // C. TAGIHAN MERCHANT (Piutang)
        // Uang yang ada di luar (Status pending/loan)
        $tagihanMerchant = Transaction::where('type', 'loan') 
            ->where('status', 'pending') 
            ->sum('total_amount');

        // D. PROFIT / PENDAPATAN
        // Cara 1: Jika ada Wallet khusus profit ('LKBB_PROFIT')
        // $profit = Wallet::where('type', 'LKBB_PROFIT')->value('balance');
        
        // Cara 2 (Sementara): Hitung margin 5% dari transaksi lunas bulan ini
        $totalTrxBulanIni = Transaction::where('status', 'lunas')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('total_amount');
        $profit = $totalTrxBulanIni * 0.05; 


        // ---------------------------------------------------------
        // 2. DATA GRAFIK (Cash Flow Real dari Ledger)
        // ---------------------------------------------------------
        
        // Mengambil histori uang masuk (CREDIT) ke Wallet LKBB 6 bulan terakhir
        // Ini lebih akurat daripada tabel Transaction karena mencatat mutasi uang asli
        $cashflowData = [];
        if ($lkbbWallet) {
            $cashflowData = LedgerEntry::select(
                    DB::raw('SUM(amount) as total'),
                    DB::raw('MONTH(created_at) as month')
                )
                ->where('wallet_id', $lkbbWallet->id)
                ->where('entry_type', 'CREDIT') // Uang Masuk
                ->where('created_at', '>=', Carbon::now()->subMonths(6))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();
        }
        
        // Mapping data chart
        $chartLabels = [];
        $chartValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthNum = $date->month;
            $chartLabels[] = $date->format('M');
            $chartValues[] = $cashflowData[$monthNum] ?? 0;
        }


        // ---------------------------------------------------------
        // 3. TABEL TRANSAKSI TERBARU
        // ---------------------------------------------------------
        $recentTransactions = Transaction::with('user')
            ->latest()
            ->take(5)
            ->get();

        // BARU: Data Volume Transaksi Harian (Untuk Line Chart Sebelah Kanan)
        $dailyVolumeData = Transaction::select(
                DB::raw('DATE(created_at) as date'), 
                DB::raw('COUNT(*) as total_trx')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(7)) // 7 Hari terakhir
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
            
        $lineChartLabels = [];
        $lineChartValues = [];
        
        // Loop 7 hari terakhir agar grafik tetap jalan meski hari itu 0 transaksi
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now()->subDays($i)->isoFormat('ddd'); // Sen, Sel, Rab...
            
            $record = $dailyVolumeData->firstWhere('date', $date);
            
            $lineChartLabels[] = $dayName;
            $lineChartValues[] = $record ? $record->total_trx : 0;
        }

        return [
            'modalKerja' => $modalKerja,
            'saldoDonasi' => $saldoDonasi,
            'tagihanMerchant' => $tagihanMerchant,
            'profit' => $profit,
            'recentTransactions' => $recentTransactions,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,

            // Grafik 1 (Bar)
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,

            // Grafik 2 (Line - Baru)
            'lineChartLabels' => $lineChartLabels,
            'lineChartValues' => $lineChartValues,
        ];
    }
    #[Computed]
    public function pendingAlerts()
    {
        return [
            'supply_chain' => \App\Models\SupplyChain::where('status', 'PENDING')->count(),
            // Placeholder untuk modul selanjutnya:
            'users' => 0, // Ganti dengan: User::where('is_approved', false)->count() nanti
            'withdrawals' => 0 // Ganti dengan: Withdrawal::where('status', 'PENDING')->count() nanti
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Utama</h1>
            <p class="text-gray-500 text-sm mt-1">Overview performa keuangan dan operasional hari ini.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="relative">
                <input type="text" placeholder="Cari data..." class="pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button class="p-2 bg-white border border-gray-200 rounded-full hover:bg-gray-50 relative">
                <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg shadow-blue-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <p class="text-blue-100 text-sm font-medium mb-1">Total Modal Kerja</p>
                <h3 class="text-3xl font-bold">
                    Rp {{ number_format($modalKerja, 0, ',', '.') }}
                </h3>
                <div class="mt-4 flex items-center gap-2 text-xs bg-blue-500/30 w-fit px-2 py-1 rounded-lg backdrop-blur-sm">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Wallet Utama: Active</span>
                </div>
            </div>
        </div>

        <div class="bg-orange-50 rounded-2xl p-6 border border-orange-100 shadow-sm relative overflow-hidden">
            <div class="absolute top-4 right-4 p-2 bg-orange-100 rounded-lg text-orange-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <p class="text-gray-500 text-sm font-medium mb-1">Saldo Donasi</p>
            <h3 class="text-2xl font-bold text-gray-800">
                Rp {{ number_format($saldoDonasi, 0, ',', '.') }}
            </h3>
            <div class="mt-4 flex items-center gap-1 text-xs text-green-600 font-medium">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                <span>Siap disalurkan</span>
            </div>
        </div>

        <div class="bg-red-50 rounded-2xl p-6 border border-red-100 shadow-sm relative overflow-hidden">
            <div class="absolute top-4 right-4 p-2 bg-red-100 rounded-lg text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <p class="text-gray-500 text-sm font-medium mb-1">Tagihan Merchant</p>
            <h3 class="text-2xl font-bold text-red-600">
                Rp {{ number_format($tagihanMerchant, 0, ',', '.') }}
            </h3>
            <div class="mt-4 flex items-center gap-1 text-xs text-red-500 font-medium">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Belum Lunas</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm relative overflow-hidden">
             <div class="absolute top-4 right-4 p-2 bg-green-100 rounded-lg text-green-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-gray-500 text-sm font-medium mb-1">Estimasi Profit</p>
            <h3 class="text-2xl font-bold text-green-600">
                + Rp {{ number_format($profit, 0, ',', '.') }}
            </h3>
            <div class="mt-4 flex items-center gap-1 text-xs text-green-600 font-medium">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <span>Bulan ini</span>
            </div>
        </div>
    </div>
    {{-- Fungsi baru --}}
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    
    @if($this->pendingAlerts['supply_chain'] > 0)
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg shadow-sm flex items-start justify-between">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-amber-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <h3 class="text-sm font-bold text-amber-800">Menunggu Approval Pembiayaan</h3>
                <p class="text-xs text-amber-700 mt-1">Ada <strong>{{ $this->pendingAlerts['supply_chain'] }}</strong> pengajuan dari Merchant.</p>
            </div>
        </div>
        <a href="{{ route('supply-chain.approval') }}" class="text-xs font-bold text-amber-600 hover:text-amber-800 underline">Review</a>
    </div>
    @endif

    @if($this->pendingAlerts['withdrawals'] > 0)
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg shadow-sm flex items-start justify-between">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <div>
                <h3 class="text-sm font-bold text-blue-800">Permintaan Pencairan Dana</h3>
                <p class="text-xs text-blue-700 mt-1">Ada <strong>{{ $this->pendingAlerts['withdrawals'] }}</strong> antrean penarikan.</p>
            </div>
        </div>
        <a href="#" class="text-xs font-bold text-blue-600 hover:text-blue-800 underline">Proses</a>
    </div>
    @endif

    @if($this->pendingAlerts['users'] > 0)
    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-r-lg shadow-sm flex items-start justify-between">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            <div>
                <h3 class="text-sm font-bold text-purple-800">Verifikasi Akun Baru</h3>
                <p class="text-xs text-purple-700 mt-1">Ada <strong>{{ $this->pendingAlerts['users'] }}</strong> pendaftar baru.</p>
            </div>
        </div>
        <a href="#" class="text-xs font-bold text-purple-600 hover:text-purple-800 underline">Verifikasi</a>
    </div>
    @endif

</div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm" wire:ignore>
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Grafik Penyerapan Donasi</h3>
                    <p class="text-xs text-gray-400">Total penyaluran dana per bulan</p>
                </div>
                <select class="text-xs border-gray-200 rounded-lg text-gray-500 bg-gray-50">
                    <option>6 Bulan Terakhir</option>
                </select>
            </div>
            <div class="relative h-64 w-full">
                 <canvas id="cashflowChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm" wire:ignore>
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Volume Transaksi Harian</h3>
                    <p class="text-xs text-gray-400">Jumlah transaksi tercatat</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span class="text-xs text-gray-500">Hari Ini</span>
                </div>
            </div>
            <div class="relative h-64 w-full">
                 <canvas id="volumeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-8">
         <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-gray-800 text-lg">Transaksi Terbaru</h3>
            <button class="text-sm text-blue-600 font-semibold hover:text-blue-700">Lihat Semua</button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-400 border-b border-gray-100 uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">ID Transaksi</th>
                        <th class="py-3 px-4 font-semibold">Merchant / User</th>
                        <th class="py-3 px-4 font-semibold">Tanggal</th>
                        <th class="py-3 px-4 font-semibold">Nominal</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                    @forelse($recentTransactions as $trx)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="py-4 px-4 font-bold text-gray-700 group-hover:text-blue-600 transition">
                            {{ $trx->order_id ?? '#TRX-'.$trx->id }}
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-medium text-gray-800">{{ $trx->user->name ?? 'Guest' }}</div>
                            <div class="text-xs text-gray-400 capitalize">{{ $trx->user->role ?? 'User' }}</div>
                        </td>
                        <td class="py-4 px-4 text-gray-500">{{ $trx->created_at->format('d M Y') }}</td>
                        <td class="py-4 px-4 font-bold text-gray-800">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                        <td class="py-4 px-4">
                            @php
                                $statusClass = match($trx->status) {
                                    'lunas', 'success', 'paid' => 'bg-green-50 text-green-600 border border-green-100',
                                    'pending' => 'bg-yellow-50 text-yellow-600 border border-yellow-100',
                                    'failed' => 'bg-red-50 text-red-600 border border-red-100',
                                    default => 'bg-gray-50 text-gray-600 border border-gray-100'
                                };
                            @endphp
                            <span class="{{ $statusClass }} px-3 py-1 rounded-full text-xs font-bold capitalize">
                                {{ $trx->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-8 text-gray-400">Belum ada transaksi data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Gunakan livewire:navigated agar script berjalan lancar saat perpindahan halaman SPA
    document.addEventListener('livewire:navigated', () => {
        
        // --- 1. CONFIG BAR CHART (Cashflow/Donasi) ---
        const ctxBar = document.getElementById('cashflowChart');
        if (ctxBar) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Penyaluran',
                        data: @json($chartValues),
                        backgroundColor: '#3B82F6',
                        borderRadius: 4,
                        barThickness: 25,
                        hoverBackgroundColor: '#2563EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { borderDash: [4, 4], drawBorder: false, color: '#F3F4F6' },
                            ticks: { font: { size: 10 } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        }

        // --- 2. CONFIG LINE CHART (Volume Transaksi) ---
        const ctxLine = document.getElementById('volumeChart');
        if (ctxLine) {
            const gradient = ctxLine.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: @json($lineChartLabels),
                    datasets: [{
                        label: 'Transaksi',
                        data: @json($lineChartValues),
                        borderColor: '#3B82F6',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3B82F6',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { display: false, beginAtZero: true },
                        x: { 
                            grid: { display: false },
                            ticks: { color: '#9CA3AF', font: { size: 10 } }
                        }
                    }
                }
            });
        }
    });
</script>