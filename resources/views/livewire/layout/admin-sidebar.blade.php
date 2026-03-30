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
    $isMasterDataActive = request()->routeIs('admin.users.*', 'admin.mahasiswa.*', 'admin.merchant.*', 'admin.pemasok.*', 'admin.investor.*', 'admin.donatur.*');
    $isOperasionalActive = request()->routeIs('admin.monitoring.*', 'admin.distribusi.*', 'admin.po.*');
    $isKeuanganActive = request()->routeIs('admin.setoran.*', 'admin.bagihasil.*');
@endphp

<aside 
    :class="sidebarOpen ? 'w-72' : 'w-20'"
    class="bg-white border-r border-gray-100 min-h-screen flex-col transition-all duration-300 ease-in-out relative hidden md:flex z-50 shadow-sm"
>
    
    {{-- Toggle Button --}}
    <button 
        @click="sidebarOpen = !sidebarOpen"
        class="absolute -right-3 top-9 bg-white border border-gray-200 text-gray-500 rounded-full p-1.5 shadow-md hover:text-[#0A60B3] hover:border-[#0A60B3]/30 transition-all z-50 focus:outline-none"
    >
        <svg x-show="sidebarOpen" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
        <svg x-show="!sidebarOpen" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
    </button>

    {{-- Logo Area --}}
    <div class="h-20 flex items-center px-4 border-b border-gray-100 overflow-hidden whitespace-nowrap bg-gray-50/50">
        <div class="flex items-center gap-3 transition-all duration-300">
            <div class="p-2 bg-white border border-gray-100 rounded-xl flex-shrink-0 shadow-sm">
                <img src="{{ asset('images/logo-lapi.png') }}" alt="SCFS" class="h-8 w-8 object-contain">
            </div>
            
            <div x-show="sidebarOpen" x-transition.opacity.duration.300ms class="transition-opacity">
                <h1 class="font-extrabold text-gray-900 text-lg tracking-wide">SCFS ADMIN</h1>
                <p class="text-[9px] text-[#0A60B3] uppercase tracking-widest font-extrabold">LAPI ITB Panel</p>
            </div>
        </div>
    </div>

    {{-- Navigasi Menu --}}
    <nav class="flex-1 px-3 py-6 space-y-2 overflow-y-auto overflow-x-hidden scrollbar-hide">
        
        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest whitespace-nowrap">
            Menu Utama
        </div>

        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('admin.dashboard') 
                ? 'bg-[#137FEC] text-white shadow-md shadow-blue-200 border border-[#137FEC]/10' 
                : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}"
            :class="sidebarOpen ? '' : 'justify-center'" 
            title="Dashboard">

            <svg class="w-5 h-5 flex-shrink-0 transition-colors
                {{ request()->routeIs('admin.dashboard') 
                    ? 'text-white' 
                    : 'text-gray-400 group-hover:text-gray-500' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z
                    M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z
                    M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z
                    M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>

            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">
                Dashboard
            </span>

        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-6 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest whitespace-nowrap">
            Verifikasi & Approval
        </div>

        <a href="{{ route('admin.verification') }}" 
            class="flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('admin.verification') 
                ? 'bg-[#137FEC] text-white shadow-md shadow-blue-200 border border-[#137FEC]/10' 
                : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}"
            :class="sidebarOpen ? '' : 'justify-center'" 
            title="Verifikasi Mahasiswa">

            <svg class="w-5 h-5 flex-shrink-0 transition-colors
                {{ request()->routeIs('admin.verification') 
                    ? 'text-white' 
                    : 'text-gray-400 group-hover:text-gray-500' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">
                Verifikasi Mahasiswa
            </span>

            <!-- Badge tetap -->
            <span x-show="sidebarOpen" 
                class="ml-auto bg-rose-100 text-rose-700 text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm border border-rose-200">
                3
            </span>

            <span x-show="!sidebarOpen" 
                class="absolute top-2 right-2 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-white">
            </span>

        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest whitespace-nowrap">
            Master Data
        </div>

        <div x-data="{ masterDataOpen: {{ $isMasterDataActive ? 'true' : 'false' }} }" class="mt-1">
            <button 
                @click="if(!sidebarOpen) sidebarOpen = true; masterDataOpen = !masterDataOpen"
                class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 group
                {{ $isMasterDataActive 
                    ? 'bg-[#137FEC] text-white shadow-md shadow-blue-200 border border-[#137FEC]/10' 
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}"
                :class="sidebarOpen ? '' : 'justify-center'" 
                title="Manajemen Pengguna">

                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 transition-colors
                        {{ $isMasterDataActive 
                            ? 'text-white' 
                            : 'text-gray-400 group-hover:text-gray-500' }}"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>

                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">
                        Manajemen Pengguna
                    </span>
                </div>

                <svg 
                    x-show="sidebarOpen" 
                    :class="{'rotate-180': masterDataOpen}" 
                    class="w-4 h-4 transition-transform duration-300
                    {{ $isMasterDataActive ? 'text-white' : 'text-gray-400' }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 9l-7 7-7-7" />
                </svg>

            </button>

            {{-- SUB-MENU MASTER DATA (WITH ICONS) --}}
            <div x-show="masterDataOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" 
                 class="mt-1.5 space-y-1 px-3">
                
                <a href="{{ route('admin.users.index') }}" wire:navigate class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Akun Pengguna
                </a>

                <a href="{{ route('admin.mahasiswa.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.mahasiswa.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.mahasiswa.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v6" />
                    </svg>
                    Data Mahasiswa
                </a>
                
                <a href="{{ route('admin.merchant.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.merchant.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.merchant.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Data Merchant
                </a>
                
                <a href="{{ route('admin.pemasok.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.pemasok.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.pemasok.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14H5a2 2 0 00-2 2v.5a1.5 1.5 0 001.5 1.5h1.5m8-4h3a2 2 0 012 2v.5a1.5 1.5 0 01-1.5 1.5h-1.5m-8-4v-4a2 2 0 012-2h4a2 2 0 012 2v4m-8 4v2m8-4v2M8 18a2 2 0 100-4 2 2 0 000 4zm8 0a2 2 0 100-4 2 2 0 000 4z" />
                    </svg>
                    Data Pemasok
                </a>
                
                <a href="{{ route('admin.investor.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.investor.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.investor.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    Data Investor
                </a>
                
                <a href="{{ route('admin.donatur.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.donatur.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.donatur.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    Data Donatur
                </a>
            </div>
        </div>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest whitespace-nowrap">
            Operasional
        </div>

        <div x-data="{ operasionalOpen: {{ $isOperasionalActive ? 'true' : 'false' }} }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; operasionalOpen = !operasionalOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 group {{ $isOperasionalActive ? 'bg-blue-50 text-[#0A60B3]' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Operasional Transaksi">
                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 {{ $isOperasionalActive ? 'text-[#0A60B3]' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">Operasional Transaksi</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': operasionalOpen}" class="w-4 h-4 text-gray-400 {{ $isOperasionalActive ? 'text-[#0A60B3]' : '' }} transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            {{-- SUB-MENU OPERASIONAL (WITH ICONS) --}}
            <div x-show="operasionalOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" 
                 class="mt-1.5 space-y-1 px-3">
                 
                <a href="{{ route('admin.monitoring.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.monitoring.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.monitoring.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Monitoring Transaksi
                </a>
                
                <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.distribusi.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.distribusi.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    Distribusi Saldo
                </a>
                
                <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.po.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.po.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    PO & Pendanaan
                </a>
            </div>
        </div>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest whitespace-nowrap">
            Keuangan
        </div>

        <div x-data="{ keuanganOpen: {{ $isKeuanganActive ? 'true' : 'false' }} }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; keuanganOpen = !keuanganOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 group {{ $isKeuanganActive ? 'bg-blue-50 text-[#0A60B3]' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Keuangan & Settlement">
                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 {{ $isKeuanganActive ? 'text-[#0A60B3]' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">Keuangan & Settlement</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': keuanganOpen}" class="w-4 h-4 text-gray-400 {{ $isKeuanganActive ? 'text-[#0A60B3]' : '' }} transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            {{-- SUB-MENU KEUANGAN (WITH ICONS) --}}
            <div x-show="keuanganOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" 
                 class="mt-1.5 space-y-1 px-3">
                 
                <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.setoran.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.setoran.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Setoran Tunai
                </a>
                
                <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.bagihasil.*') ? 'text-[#0A60B3] bg-blue-50/50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 {{ request()->routeIs('admin.bagihasil.*') ? 'text-[#0A60B3]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    Riwayat Bagi Hasil
                </a>
            </div>
        </div>

        <div class="h-4"></div>
    </nav>

    {{-- User Profile & Logout --}}
    <div class="p-4 border-t border-gray-100 bg-gray-50">
        <div class="flex items-center gap-3 mb-4 px-1" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="h-9 w-9 flex-shrink-0 rounded-full bg-gradient-to-tr from-[#0A60B3] to-blue-400 flex items-center justify-center text-white font-extrabold text-sm shadow-inner border border-white/20 relative">
                {{ substr(Auth::user()->name, 0, 2) }}
                {{-- Status Online Green Dot --}}
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
            </div>
            
            <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                <p class="text-sm font-bold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-[#0A60B3] font-medium truncate w-32">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <button wire:click="logout" 
                class="w-full flex items-center px-3 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 transition-all duration-200 shadow-sm focus:outline-none"
                :class="sidebarOpen ? 'justify-center' : 'justify-center bg-transparent border-0 hover:border-0 shadow-none'"
                title="Keluar">
            <svg class="w-5 h-5 flex-shrink-0" :class="sidebarOpen ? 'mr-2.5' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span x-show="sidebarOpen" x-transition>Keluar Sistem</span>
        </button>
    </div>

</aside>