<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $isMasterDataActive = request()->routeIs('admin.mahasiswa.*', 'admin.merchant.*', 'admin.pemasok.*', 'admin.investor.*', 'admin.donatur.*');
    $isOperasionalActive = request()->routeIs('admin.monitoring.*', 'admin.distribusi.*', 'admin.po.*');
    $isKeuanganActive = request()->routeIs('admin.setoran.*', 'admin.bagihasil.*');
@endphp

<aside 
    :class="sidebarOpen ? 'w-72' : 'w-20'"
    class="bg-white border-r border-gray-200 min-h-screen flex-col transition-all duration-300 ease-in-out relative hidden md:flex"
>
    
    <button 
        @click="sidebarOpen = !sidebarOpen"
        class="absolute -right-3 top-9 bg-white border border-gray-200 text-gray-500 rounded-full p-1 shadow-sm hover:text-blue-600 hover:border-blue-300 transition-colors z-50"
    >
        <svg x-show="sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        <svg x-show="!sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    </button>

    <div class="h-20 flex items-center px-4 border-b border-gray-100 overflow-hidden whitespace-nowrap">
        <div class="flex items-center gap-3 transition-all duration-300">
            <div class="p-2 bg-blue-50 rounded-lg flex-shrink-0">
                <img src="{{ asset('images/logo-lapi.png') }}" alt="SCFS" class="h-8 w-8 object-contain">
            </div>
            
            <div x-show="sidebarOpen" x-transition.opacity.duration.300ms class="transition-opacity">
                <h1 class="font-bold text-gray-800 text-lg tracking-wide">SCFS ADMIN</h1>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">LAPI ITB Panel</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto overflow-x-hidden">
        
        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Menu Utama
        </div>

        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Dashboard">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Dashboard</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Verifikasi & Approval
        </div>

        <a href="{{ route('admin.verification') }}" 
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('admin.verification') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Verifikasi Mahasiswa">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.verification') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Verifikasi Mahasiswa</span>
            
            <span x-show="sidebarOpen" class="ml-auto bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded-full border border-red-200">3</span>
            <span x-show="!sidebarOpen" class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Master Data
        </div>

        <div x-data="{ masterDataOpen: {{ $isMasterDataActive ? 'true' : 'false' }} }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; masterDataOpen = !masterDataOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group {{ $isMasterDataActive ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Manajemen Pengguna">
                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 {{ $isMasterDataActive ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">Manajemen Pengguna</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': masterDataOpen}" class="w-4 h-4 {{ $isMasterDataActive ? 'text-blue-600' : 'text-gray-400' }} transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="masterDataOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1 space-y-1 px-2">
                <a href="{{ route('admin.mahasiswa.index') }}" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.mahasiswa.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.mahasiswa.*') ? 'text-blue-600' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Data Mahasiswa
                </a>
                
                <a href="{{ route('admin.merchant.index') }}" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.merchant.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.merchant.*') ? 'text-blue-600' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Data Merchant
                </a>
                <a href="{{ route('admin.pemasok.index') }}" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.pemasok.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.pemasok.*') ? 'text-blue-600' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                    Data Pemasok
                </a>
                <a href="{{ route('admin.investor.index') }}" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.investor.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.investor.*') ? 'text-blue-600' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    Data Investor
                </a>
                <a href="#" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.donatur.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    Data Donatur
                </a>
            </div>
        </div>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Operasional
        </div>

        <div x-data="{ operasionalOpen: {{ $isOperasionalActive ? 'true' : 'false' }} }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; operasionalOpen = !operasionalOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group {{ $isOperasionalActive ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Operasional Transaksi">
                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 {{ $isOperasionalActive ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">Operasional Transaksi</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': operasionalOpen}" class="w-4 h-4 {{ $isOperasionalActive ? 'text-blue-600' : 'text-gray-400' }} transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="operasionalOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1 space-y-1 px-2">
                <a href="#" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.monitoring.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                    Monitoring Transaksi
                </a>
                <a href="#" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.distribusi.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                    Distribusi Saldo
                </a>
                <a href="#" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.po.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    PO & Pendanaan
                </a>
            </div>
        </div>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Keuangan
        </div>

        <div x-data="{ keuanganOpen: {{ $isKeuanganActive ? 'true' : 'false' }} }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; keuanganOpen = !keuanganOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group {{ $isKeuanganActive ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Keuangan & Settlement">
                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 {{ $isKeuanganActive ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">Keuangan & Settlement</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': keuanganOpen}" class="w-4 h-4 {{ $isKeuanganActive ? 'text-blue-600' : 'text-gray-400' }} transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="keuanganOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1 space-y-1 px-2">
                <a href="#" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.setoran.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Setoran Tunai
                </a>
                <a href="#" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.bagihasil.*') ? 'text-blue-700 bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
                    Riwayat Bagi Hasil
                </a>
            </div>
        </div>

        <div class="h-4"></div>
    </nav>

    <div class="p-4 border-t border-gray-100 bg-gray-50/50">
        <div class="flex items-center gap-3 mb-4 px-1" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="h-9 w-9 flex-shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm shadow-sm border border-blue-200">
                {{ substr(Auth::user()->name, 0, 2) }}
            </div>
            
            <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                <p class="text-sm font-bold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-gray-500 truncate w-32">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <button wire:click="logout" 
                class="w-full flex items-center px-3 py-2 text-sm font-medium text-red-600 bg-white border border-red-100 rounded-lg hover:bg-red-50 hover:border-red-200 transition-all duration-200 shadow-sm"
                :class="sidebarOpen ? 'justify-center' : 'justify-center border-0 bg-transparent hover:bg-red-50'"
                title="Keluar">
            <svg class="w-5 h-5 flex-shrink-0" :class="sidebarOpen ? 'mr-2' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span x-show="sidebarOpen" x-transition>Keluar</span>
        </button>
    </div>

</aside>