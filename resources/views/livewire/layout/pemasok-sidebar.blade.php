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
    // Logika untuk mendeteksi menu mana yang sedang aktif
    $isDashboardActive = request()->routeIs('pemasok.dashboard');
    $isPesananActive = request()->routeIs('pemasok.pesanan-masuk') || request()->is('pemasok/pesanan*'); 
    $isKeuanganActive = request()->is('pemasok/keuangan*');
@endphp

<aside 
    :class="sidebarOpen ? 'w-72' : 'w-20'"
    class="bg-white border-r border-gray-200 min-h-screen flex-col transition-all duration-300 ease-in-out relative hidden md:flex z-40"
>
    
    <button 
        @click="sidebarOpen = !sidebarOpen"
        class="absolute -right-3 top-9 bg-white border border-gray-200 text-gray-500 rounded-full p-1 shadow-sm hover:text-orange-600 hover:border-orange-300 transition-colors z-50"
    >
        <svg x-show="sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        <svg x-show="!sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    </button>

    <div class="h-20 flex items-center px-4 border-b border-gray-100 overflow-hidden whitespace-nowrap shrink-0">
        <div class="flex items-center gap-3 transition-all duration-300">
            <div class="p-2 bg-orange-500 rounded-lg flex-shrink-0 text-white flex items-center justify-center w-10 h-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            
            <div x-show="sidebarOpen" x-transition.opacity.duration.300ms class="transition-opacity">
                <h1 class="font-bold text-gray-800 text-lg tracking-wide">SCFS Pemasok</h1>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Dapur Pusat</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto overflow-x-hidden">
        
        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Menu Utama
        </div>

        <a href="{{ route('pemasok.dashboard') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ $isDashboardActive ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Dashboard">
            <svg class="w-5 h-5 flex-shrink-0 {{ $isDashboardActive ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Dashboard</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Operasional
        </div>

        <div x-data="{ pesananOpen: {{ $isPesananActive ? 'true' : 'false' }} }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; pesananOpen = !pesananOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group {{ $isPesananActive ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Manajemen Pesanan">
                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 {{ $isPesananActive ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">Pesanan Masuk</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': pesananOpen}" class="w-4 h-4 {{ $isPesananActive ? 'text-orange-600' : 'text-gray-400' }} transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="pesananOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1 space-y-1 px-2">
                <a href="{{ route('pemasok.pesanan-masuk') }}" wire:navigate 
                   class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('pemasok.pesanan-masuk') ? 'text-orange-700 bg-orange-50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    Daftar Pesanan
                </a>
                <a href="{{ route('pemasok.riwayat-produksi') }}" wire:navigate 
                    class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('pemasok.riwayat-produksi') ? 'text-orange-700 bg-orange-50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    Riwayat Produksi
                </a>
            </div>
        </div>

        <a href="{{ route('pemasok.pengiriman') }}" wire:navigate
            class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('pemasok.pengiriman') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
            :class="sidebarOpen ? '' : 'justify-center'" title="Pengiriman & Logistik">
                <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('pemasok.pengiriman') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                <span x-show="sidebarOpen" x-transition class="ml-3 font-semibold transition-opacity duration-300">Pengiriman Logistik</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Keuangan
        </div>

        <div x-data="{ keuanganOpen: {{ $isKeuanganActive ? 'true' : 'false' }} }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; keuanganOpen = !keuanganOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group {{ $isKeuanganActive ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Keuangan">
                <div class="flex items-center">
                    <svg class="w-5 h-5 flex-shrink-0 {{ $isKeuanganActive ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <span x-show="sidebarOpen" class="ml-3 font-semibold whitespace-nowrap">Dompet & Saldo</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': keuanganOpen}" class="w-4 h-4 {{ $isKeuanganActive ? 'text-orange-600' : 'text-gray-400' }} transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="keuanganOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1 space-y-1 px-2">
                <a href="#" class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                    Informasi Saldo
                </a>
                
                {{-- MENU BARU: Pengajuan Dana LKBB --}}
                <a href="{{ route('pemasok.pengajuan-dana-lkbb') }}" wire:navigate 
                   class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('pemasok.pengajuan-dana-lkbb') ? 'text-orange-700 bg-orange-50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    Pengajuan Dana LKBB
                </a>

                <a href="{{ route('pemasok.tarik-dana') }}" wire:navigate 
                   class="flex items-center pl-10 pr-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('pemasok.tarik-dana') ? 'text-orange-700 bg-orange-50 font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    Tarik Dana (Withdraw)
                </a>
            </div>
        </div>

         {{-- Inventaris --}}
        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Inventory
        </div>
        <a href="{{ route('pemasok.inventaris') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('pemasok.inventaris') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Stok & Produk">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('pemasok.inventaris') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 font-semibold transition-opacity duration-300">Stok & Produk</span>
        </a>
        
        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Laporan
        </div>
        <a href="{{ route('pemasok.laporan') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('pemasok.laporan') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Laporan & Analitik">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('pemasok.laporan') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 font-semibold transition-opacity duration-300">Laporan & Analitik</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-6 text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Akun & Pengaturan
        </div>
        <a href="{{ route('pemasok.profil') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('pemasok.profil') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Pengaturan Profil">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('pemasok.profil') ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 font-semibold transition-opacity duration-300">Pengaturan Profil</span>
        </a>

        <div class="h-4"></div>
    </nav>

    <div class="p-4 border-t border-gray-100 bg-gray-50/50 shrink-0">
        <div class="flex items-center gap-3 mb-4 px-1" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="h-9 w-9 flex-shrink-0 rounded-full bg-orange-100 flex items-center justify-center text-orange-700 font-bold text-sm shadow-sm border border-orange-200">
                {{ substr(Auth::user()->name ?? 'P', 0, 2) }}
            </div>
            
            <div x-show="sidebarOpen" x-transition class="overflow-hidden flex-1">
                <p class="text-sm font-bold text-gray-800 truncate">{{ Auth::user()->name ?? 'Pemasok' }}</p>
                <p class="text-[10px] text-gray-500 truncate w-32">{{ Auth::user()->email ?? 'pemasok@scfs.com' }}</p>
            </div>
        </div>

        <button wire:click="logout" 
                class="w-full flex items-center px-3 py-2 text-sm font-medium text-red-600 bg-white border border-red-100 rounded-lg hover:bg-red-50 hover:border-red-200 transition-all duration-200 shadow-sm"
                :class="sidebarOpen ? 'justify-center' : 'justify-center border-0 bg-transparent hover:bg-red-50 shadow-none'"
                title="Keluar">
            <svg class="w-5 h-5 flex-shrink-0" :class="sidebarOpen ? 'mr-2' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span x-show="sidebarOpen" x-transition>Keluar</span>
        </button>
    </div>

</aside>