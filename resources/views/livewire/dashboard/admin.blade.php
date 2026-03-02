<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\MahasiswaProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new 
#[Layout('layouts.app')]
class extends Component {
    
    public function mount()
    {
        // Proteksi: Hanya Admin yang boleh masuk
        if (Auth::user()->role !== 'admin') {
            return $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function with()
    {
        // 1. STATISTIK KARTU ATAS
        // Total mahasiswa yang statusnya disetujui
        $mahasiswaTerverifikasi = MahasiswaProfile::where('status_verifikasi', 'disetujui')->count();

        // Jumlah transaksi hari ini
        $transaksiHariIni = Transaction::whereDate('created_at', Carbon::today())->count();

        // Total perputaran dana (transaksi berstatus lunas)
        $totalDana = Transaction::where('status', 'lunas')->sum('total_amount');

        // 2. AKTIVITAS TERBARU (5 Transaksi Terakhir)
        $recentActivities = Transaction::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($trx) {
                return [
                    'name' => $trx->user->name ?? 'Sistem / Guest',
                    'id' => $trx->order_id ?? ('TRX-' . $trx->id),
                    'type' => ucwords(str_replace('_', ' ', $trx->type)), // Misal: donation -> Donation
                    'status' => $trx->status == 'lunas' ? 'Selesai' : ($trx->status == 'pending' ? 'Tertunda' : 'Gagal'),
                    'amount' => $trx->total_amount,
                    'time' => $trx->created_at->diffForHumans(),
                    'avatar' => strtoupper(substr($trx->user->name ?? 'S', 0, 2))
                ];
            });

        // 3. DATA GRAFIK (Tren Volume Transaksi 6 Bulan Terakhir)
        $chartData = Transaction::select(
                DB::raw('SUM(total_amount) as total'),
                DB::raw('MONTH(created_at) as month')
            )
            ->where('status', 'lunas')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $chartLabels = [];
        $chartValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $chartLabels[] = $date->isoFormat('MMM YYYY');
            $chartValues[] = $chartData[$date->month] ?? 0;
        }

        return [
            'mahasiswaTerverifikasi' => $mahasiswaTerverifikasi,
            'transaksiHariIni' => $transaksiHariIni,
            'totalDana' => $totalDana,
            'recentActivities' => $recentActivities,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
        ];
    }
}; ?>

<div class="space-y-8 font-sans text-gray-800 p-6 md:p-8 w-full relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Dashboard Utama Admin</h2>
            <p class="text-gray-500 mt-1">Ringkasan performa ekosistem SCFS hari ini, {{ date('d M Y') }}</p>
        </div>
        <button class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Unduh Laporan
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 opacity-50 rounded-full -mr-6 -mt-6 transition-transform group-hover:scale-150"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <span class="flex items-center text-[10px] font-bold text-green-600 bg-green-50 px-2.5 py-1 rounded-full border border-green-100 uppercase tracking-wider">
                    Aktif
                </span>
            </div>
            <div class="relative z-10">
                <h3 class="text-3xl font-extrabold text-gray-900">{{ number_format($mahasiswaTerverifikasi, 0, ',', '.') }}</h3>
                <p class="text-gray-500 text-sm font-medium mt-1">Mahasiswa Terverifikasi</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 opacity-50 rounded-full -mr-6 -mt-6 transition-transform group-hover:scale-150"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="p-3 bg-orange-50 text-orange-600 rounded-xl group-hover:bg-orange-500 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                </div>
                <span class="flex items-center text-[10px] font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100 uppercase tracking-wider">
                    Hari Ini
                </span>
            </div>
            <div class="relative z-10">
                <h3 class="text-3xl font-extrabold text-gray-900">{{ number_format($transaksiHariIni, 0, ',', '.') }}</h3>
                <p class="text-gray-500 text-sm font-medium mt-1">Transaksi Berjalan</p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 p-6 rounded-2xl shadow-lg relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="p-3 bg-white/20 text-white rounded-xl backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="flex items-center text-[10px] font-bold text-indigo-100 bg-white/20 px-2.5 py-1 rounded-full uppercase tracking-wider">
                    All Time
                </span>
            </div>
            <div class="relative z-10">
                <h3 class="text-2xl lg:text-3xl font-extrabold text-white truncate">Rp {{ number_format($totalDana, 0, ',', '.') }}</h3>
                <p class="text-indigo-100 text-sm font-medium mt-1">Total Perputaran Dana</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm lg:col-span-2" wire:ignore>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Tren Transaksi Bulanan</h3>
                    <p class="text-gray-500 text-sm mt-0.5">Volume dana transaksi sukses dalam 6 bulan terakhir</p>
                </div>
            </div>
            
            <div class="relative h-72 w-full">
                <canvas id="adminTrendChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full lg:col-span-1">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30 rounded-t-2xl">
                <h3 class="text-base font-bold text-gray-900">Aktivitas Terbaru</h3>
                <a href="{{ route('admin.monitoring.index') }}" class="text-indigo-600 text-xs font-bold hover:text-indigo-800 transition">Lihat &rarr;</a>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                @forelse($recentActivities as $activity)
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition group">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-indigo-50 flex items-center justify-center text-sm font-bold text-indigo-600 border border-indigo-100 flex-shrink-0">
                            {{ $activity['avatar'] }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900 text-sm truncate w-24 md:w-32 group-hover:text-indigo-600 transition">{{ $activity['name'] }}</div>
                            <div class="text-[10px] text-gray-400 truncate">{{ $activity['type'] }} &bull; {{ $activity['time'] }}</div>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-bold text-gray-900">
                            Rp {{ number_format($activity['amount'], 0, ',', '.') }}
                        </div>
                        @if($activity['status'] == 'Selesai')
                            <span class="text-[9px] font-bold text-green-600 uppercase tracking-wider">Selesai</span>
                        @elseif($activity['status'] == 'Tertunda')
                            <span class="text-[9px] font-bold text-orange-500 uppercase tracking-wider">Pending</span>
                        @else
                            <span class="text-[9px] font-bold text-red-500 uppercase tracking-wider">Gagal</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-10 text-gray-400">
                    <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <p class="text-sm font-medium">Belum ada aktivitas.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:navigated', () => {
        const ctxLine = document.getElementById('adminTrendChart');
        if (ctxLine) {
            // Hancurkan chart lama jika ada (mencegah bug tumpuk saat pindah halaman SPA)
            let chartStatus = Chart.getChart("adminTrendChart");
            if (chartStatus != undefined) { chartStatus.destroy(); }

            const gradient = ctxLine.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)'); // Indigo-600 transparan
            gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Volume Transaksi (Rp)',
                        data: @json($chartValues),
                        borderColor: '#4F46E5', // Indigo-600
                        backgroundColor: gradient,
                        borderWidth: 3,
                        tension: 0.4, // Melengkung mulus
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4F46E5',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let value = context.raw || 0;
                                    return ' Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            grid: { borderDash: [4, 4], color: '#F3F4F6', drawBorder: false },
                            ticks: { 
                                color: '#9CA3AF', 
                                font: { size: 10 },
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + 'M';
                                    if (value >= 1000) return (value / 1000) + 'k';
                                    return value;
                                }
                            } 
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: '#9CA3AF', font: { size: 11 } }
                        }
                    }
                }
            });
        }
    });
</script>