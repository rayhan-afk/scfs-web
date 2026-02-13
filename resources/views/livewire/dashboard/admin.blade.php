<?php

use Livewire\Volt\Component;

new class extends Component {
    public $activities = [
        [
            'name' => 'Budi Santoso',
            'id' => 'ID-1234',
            'type' => 'Verifikasi',
            'status' => 'Selesai',
            'amount' => null,
            'time' => '2 jam yang lalu',
            'avatar' => 'BS'
        ],
        [
            'name' => 'Siti Aminah',
            'id' => 'ID-5678',
            'type' => 'Pendanaan',
            'status' => 'Proses',
            'amount' => 25000000,
            'time' => '3 jam yang lalu',
            'avatar' => 'SA'
        ],
        [
            'name' => 'Ahmad Dani',
            'id' => 'ID-9012',
            'type' => 'Verifikasi',
            'status' => 'Tertunda',
            'amount' => null,
            'time' => '5 jam yang lalu',
            'avatar' => 'AD'
        ],
        [
            'name' => 'Kantin Teknik',
            'id' => 'ID-3344',
            'type' => 'Pencairan',
            'status' => 'Selesai',
            'amount' => 1500000,
            'time' => '1 hari yang lalu',
            'avatar' => 'KT'
        ],
    ];
}; ?>

<div class="space-y-8 font-sans text-gray-800">
    
    <div class="flex justify-between items-end">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Dashboard</h2>
            <p class="text-gray-500 mt-1">Ringkasan performa hari ini, {{ date('d M Y') }}</p>
        </div>
        <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition shadow-lg shadow-gray-200">
            + Unduh Laporan
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] hover:shadow-lg transition-all duration-300 group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <span class="flex items-center text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded-full border border-green-100">
                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    12%
                </span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-gray-900">1,250</h3>
                <p class="text-gray-500 text-sm font-medium">Mahasiswa Terverifikasi</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] hover:shadow-lg transition-all duration-300 group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-orange-50 text-orange-600 rounded-xl group-hover:bg-orange-500 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                </div>
                <span class="flex items-center text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded-full border border-green-100">
                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    5%
                </span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-gray-900">50</h3>
                <p class="text-gray-500 text-sm font-medium">Transaksi Aktif Hari Ini</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] hover:shadow-lg transition-all duration-300 group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="flex items-center text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded-full border border-green-100">
                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    15%
                </span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-gray-900">4.5M</h3>
                <p class="text-gray-500 text-sm font-medium">Total Pendanaan Pemasok</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Tren Transaksi Bulanan</h3>
                <p class="text-gray-500 text-sm">Volume pendanaan dalam 6 bulan terakhir</p>
            </div>
            <select class="text-sm border-gray-200 rounded-lg text-gray-500 focus:border-blue-500 focus:ring-blue-500">
                <option>6 Bulan Terakhir</option>
                <option>Tahun Ini</option>
            </select>
        </div>
        
        <div class="relative h-72 w-full flex items-end justify-between px-2 overflow-hidden">
            <svg class="absolute bottom-0 left-0 w-full h-full drop-shadow-sm" preserveAspectRatio="none" viewBox="0 0 1000 300">
                <defs>
                    <linearGradient id="blueGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:0.3" />
                        <stop offset="100%" style="stop-color:#3B82F6;stop-opacity:0" />
                    </linearGradient>
                </defs>
                <path d="M0,250 C150,150 250,50 350,150 C450,250 550,200 650,100 C750,0 850,250 1000,150 V300 H0 Z" fill="url(#blueGradient)" />
                <path d="M0,250 C150,150 250,50 350,150 C450,250 550,200 650,100 C750,0 850,250 1000,150" fill="none" stroke="#2563EB" stroke-width="4" stroke-linecap="round" />
            </svg>
            
            <div class="z-10 w-full flex justify-between text-xs font-semibold text-gray-400 mt-2 absolute bottom-2 px-4">
                <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>Mei</span><span>Jun</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900">Aktivitas Terbaru</h3>
            <a href="#" class="text-blue-600 text-sm font-semibold hover:text-blue-700">Lihat Semua &rarr;</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-500 text-xs uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4 rounded-tl-lg">User</th>
                        <th class="px-6 py-4">Tipe</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4 rounded-tr-lg text-right">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($activities as $activity)
                    <tr class="hover:bg-gray-50/80 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                                    {{ $activity['avatar'] }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm group-hover:text-blue-600 transition">{{ $activity['name'] }}</div>
                                    <div class="text-xs text-gray-400">{{ $activity['id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-600">
                            {{ $activity['type'] }}
                        </td>
                        <td class="px-6 py-4">
                            @if($activity['status'] == 'Selesai')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-600 border border-green-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Selesai
                                </span>
                            @elseif($activity['status'] == 'Proses')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> Proses
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-50 text-yellow-600 border border-yellow-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Tertunda
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900">
                            {{ $activity['amount'] ? 'Rp ' . number_format($activity['amount'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-400 text-right">
                            {{ $activity['time'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>