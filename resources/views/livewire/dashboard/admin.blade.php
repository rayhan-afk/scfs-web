<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\MerchantProfile;
use App\Models\Transaction;
use App\Models\MahasiswaProfile;
use Carbon\Carbon;

new 
#[Layout('layouts.app')]
class extends Component {
    
    public string $chartFilter = 'month';

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak. Halaman ini khusus Administrator.');
        }
    }

    #[Computed]
    public function stats()
    {
        $totalMahasiswa = User::where('role', 'mahasiswa')->count();
        $mahasiswaTerverifikasi = MahasiswaProfile::where('status_verifikasi', 'disetujui')->count();
        $kantinAktif = MerchantProfile::where('status_verifikasi', 'disetujui')->count();

        $transaksiHariIni = Transaction::whereDate('created_at', Carbon::today())->count();
        
        $keuangan = Transaction::whereIn('status', ['lunas', 'sukses']);
        $totalPerputaran = (clone $keuangan)->sum('total_amount');
        $pendapatanLKBB = (clone $keuangan)->sum('fee_lkbb');

        return [
            'total_mahasiswa'         => $totalMahasiswa,
            'mahasiswa_terverifikasi' => $mahasiswaTerverifikasi,
            'kantin_aktif'            => $kantinAktif,
            'transaksi_hari_ini'      => $transaksiHariIni,
            'total_perputaran'        => $totalPerputaran,
            'pendapatan_lkbb'         => $pendapatanLKBB,
        ];
    }

    #[Computed]
    public function recentActivities()
    {
        return Transaction::with('user')
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($trx) {
                return [
                    'name'   => $trx->user->name ?? 'Sistem / Guest',
                    'id'     => $trx->order_id ?? ('TRX-' . $trx->id),
                    'type'   => $trx->description ?? ucwords(str_replace('_', ' ', $trx->type)),
                    'status' => in_array($trx->status, ['lunas', 'sukses']) ? 'Selesai' : ($trx->status == 'pending' ? 'Tertunda' : 'Gagal'),
                    'amount' => $trx->total_amount,
                    'time'   => $trx->created_at->diffForHumans(),
                    'avatar' => strtoupper(substr($trx->user->name ?? 'S', 0, 2))
                ];
            });
    }

    /**
     * ENGINE GRAFIK DISERAGAMKAN DENGAN MERCHANT (Single Series)
     */
    public function getChartData()
    {
        $query = Transaction::whereIn('status', ['lunas', 'sukses']);
        
        $labels = [];
        $series = []; 

        if ($this->chartFilter === 'today') {
            $txs = (clone $query)->whereDate('created_at', Carbon::today())->get();
            $grouped = $txs->groupBy(fn($item) => Carbon::parse($item->created_at)->format('H'));

            for ($i = 6; $i <= 21; $i++) {
                $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
                $labels[] = $hour . ':00';
                $series[] = $grouped->has($hour) ? (int) $grouped->get($hour)->sum('total_amount') : 0;
            }
        } elseif ($this->chartFilter === 'month') {
            $now = Carbon::now();
            $txs = (clone $query)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->get();
            $grouped = $txs->groupBy(fn($item) => Carbon::parse($item->created_at)->format('j'));

            for ($i = 1; $i <= $now->daysInMonth; $i++) {
                $labels[] = $i . ' ' . $now->format('M');
                $series[] = $grouped->has((string)$i) ? (int) $grouped->get((string)$i)->sum('total_amount') : 0;
            }
        } elseif ($this->chartFilter === 'year') {
            $now = Carbon::now();
            $txs = (clone $query)->whereYear('created_at', $now->year)->get();
            $grouped = $txs->groupBy(fn($item) => Carbon::parse($item->created_at)->format('n'));

            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            for ($i = 1; $i <= 12; $i++) {
                $labels[] = $months[$i - 1];
                $series[] = $grouped->has((string)$i) ? (int) $grouped->get((string)$i)->sum('total_amount') : 0;
            }
        }

        return ['labels' => $labels, 'series' => $series];
    }

    public function setFilter($filter)
    {
        $this->chartFilter = $filter;
        $data = $this->getChartData();
        $this->dispatch('update-admin-chart', labels: $data['labels'], series: $data['series']);
    }

}; ?>

<div class="space-y-8 font-sans text-gray-800 p-6 md:p-8 w-full relative bg-gray-50/30 min-h-screen">
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Executive Dashboard</h2>
            <p class="text-gray-500 mt-1">Ringkasan performa dan aliran dana ekosistem SCFS ITB.</p>
        </div>
        <button class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Unduh Laporan PDF
        </button>
    </div>

    {{-- GRID METRIK (6 KARTU) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="h-14 w-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Pendaftar</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ number_format($this->stats['total_mahasiswa'], 0, ',', '.') }} <span class="text-sm font-medium text-gray-400">Akun</span></h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="h-14 w-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Mahasiswa Terverifikasi</p>
                <div class="flex items-end gap-2">
                    <h3 class="text-2xl font-extrabold text-gray-900">{{ number_format($this->stats['mahasiswa_terverifikasi'], 0, ',', '.') }}</h3>
                    @if($this->stats['total_mahasiswa'] > 0)
                        <span class="text-xs font-bold text-emerald-500 mb-1.5">({{ round(($this->stats['mahasiswa_terverifikasi'] / $this->stats['total_mahasiswa']) * 100) }}%)</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="h-14 w-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Kantin/Merchant Aktif</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ number_format($this->stats['kantin_aktif'], 0, ',', '.') }} <span class="text-sm font-medium text-gray-400">Toko</span></h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="h-14 w-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Volume Uang Beredar</p>
                <h3 class="text-xl font-extrabold text-gray-900 truncate">Rp {{ number_format($this->stats['total_perputaran'], 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 p-6 rounded-2xl shadow-lg flex items-center gap-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-6 -mt-6"></div>
            <div class="h-14 w-14 rounded-2xl bg-white/20 text-white flex items-center justify-center flex-shrink-0 backdrop-blur-sm z-10">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="z-10">
                <p class="text-[11px] font-bold text-indigo-200 uppercase tracking-wider mb-1">Pendapatan Net LKBB (Fee)</p>
                <h3 class="text-xl font-extrabold text-white truncate">Rp {{ number_format($this->stats['pendapatan_lkbb'], 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-gray-900 p-6 rounded-2xl shadow-lg flex items-center gap-5 relative overflow-hidden">
            <div class="h-14 w-14 rounded-2xl bg-gray-800 text-gray-300 flex items-center justify-center flex-shrink-0 z-10">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <div class="z-10">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Live Hari Ini
                </p>
                <h3 class="text-2xl font-extrabold text-white">{{ number_format($this->stats['transaksi_hari_ini'], 0, ',', '.') }} <span class="text-sm font-medium text-gray-500">Aktivitas</span></h3>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- GRAFIK: SEKARANG SAMA PERSIS DENGAN MERCHANT (Tinggi & Style Fixed) --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-900 text-sm">Grafik Perputaran Dana</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Total volume transaksi sukses dalam ekosistem</p>
                </div>
                
                <div class="inline-flex bg-gray-100 p-1 rounded-lg">
                    <button wire:click="setFilter('today')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'today' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Hari Ini</button>
                    <button wire:click="setFilter('month')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'month' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Bulan Ini</button>
                    <button wire:click="setFilter('year')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'year' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tahun Ini</button>
                </div>
            </div>
            
            <div class="p-6 flex-1">
                <div 
                    x-data="{
                        chart: null,
                        initChart() {
                            let options = {
                                chart: { type: 'area', height: 320, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
                                series: [{ name: 'Total Volume (Rp)', data: [] }],
                                xaxis: { categories: [], tooltip: { enabled: false }, axisBorder: { show: false } },
                                yaxis: { labels: { formatter: function(val) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val); } } },
                                stroke: { curve: 'smooth', width: 3 },
                                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                                colors: ['#4F46E5'], // Indigo theme khas Admin
                                dataLabels: { enabled: false },
                                grid: { strokeDashArray: 4, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
                                tooltip: { y: { formatter: function(val) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val); } } }
                            };
                            this.chart = new ApexCharts(this.$refs.adminChart, options);
                            this.chart.render();

                            let initialData = @js($this->getChartData());
                            this.chart.updateOptions({ xaxis: { categories: initialData.labels } });
                            this.chart.updateSeries([{ data: initialData.series }]);
                        }
                    }"
                    x-init="initChart()"
                    @update-admin-chart.window="
                        chart.updateOptions({ xaxis: { categories: $event.detail.labels } });
                        chart.updateSeries([{ data: $event.detail.series }]);
                    "
                >
                    <div x-ref="adminChart" wire:ignore></div>
                </div>
            </div>
        </div>

        {{-- AKTIVITAS TERBARU --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full lg:col-span-1 overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                <h3 class="text-base font-bold text-gray-900">Log Aktivitas Terbaru</h3>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2">
                @forelse($this->recentActivities as $activity)
                <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-xl transition group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 rounded-full bg-indigo-50 flex items-center justify-center text-sm font-bold text-indigo-600 border border-indigo-100 flex-shrink-0">
                            {{ $activity['avatar'] }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900 text-sm truncate group-hover:text-indigo-600 transition">{{ $activity['name'] }}</div>
                            <div class="text-[10px] text-gray-500 truncate">{{ $activity['type'] }} &bull; {{ $activity['time'] }}</div>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0 ml-2">
                        <div class="text-sm font-extrabold text-gray-900">
                            Rp {{ number_format($activity['amount'], 0, ',', '.') }}
                        </div>
                        @if($activity['status'] == 'Selesai')
                            <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider bg-emerald-50 px-2 py-0.5 rounded mt-1 inline-block">Selesai</span>
                        @elseif($activity['status'] == 'Tertunda')
                            <span class="text-[9px] font-bold text-orange-500 uppercase tracking-wider bg-orange-50 px-2 py-0.5 rounded mt-1 inline-block">Pending</span>
                        @else
                            <span class="text-[9px] font-bold text-red-500 uppercase tracking-wider bg-red-50 px-2 py-0.5 rounded mt-1 inline-block">Gagal</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-12 text-gray-400">
                    <div class="text-4xl mb-3 opacity-50">📭</div>
                    <p class="text-sm font-medium">Belum ada jejak transaksi.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>