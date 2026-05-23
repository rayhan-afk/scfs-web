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
    $isKeuanganActive = request()->is('pemasok/keuangan*') || request()->routeIs('pemasok.tarik-dana');
@endphp

<aside 
    x-data="{ sidebarOpen: true }"
    :class="sidebarOpen ? 'w-72' : 'w-20'"
    class="bg-[#EA580C] border-r border-[#EA580C] h-screen flex flex-col transition-all duration-300 ease-in-out relative hidden md:flex z-50 shadow-2xl text-white"
>
    
    {{-- Toggle Button --}}
    <button 
        @click="sidebarOpen = !sidebarOpen"
        class="absolute -right-3.5 top-9 bg-white border-2 border-[#EA580C] text-[#EA580C] rounded-full p-1.5 shadow-md hover:bg-orange-50 hover:scale-110 transition-all z-50 focus:outline-none"
    >
        <svg x-show="sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
        <svg x-show="!sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
    </button>

    {{-- Logo Area --}}
    <div class="h-20 flex items-center px-4 border-b border-white/10 overflow-hidden whitespace-nowrap bg-black/10 shrink-0">
        <div class="flex items-center gap-3 transition-all duration-300">
            <div class="p-2 bg-white rounded-xl flex-shrink-0 shadow-sm flex items-center justify-center w-10 h-10">
                <svg class="w-6 h-6 text-[#EA580C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            
            <div x-show="sidebarOpen" x-transition.opacity.duration.300ms class="transition-opacity">
                <h1 class="font-black text-white text-xl tracking-tight">SCFS Pemasok</h1>
                <p class="text-xs text-orange-200 uppercase tracking-widest font-bold">Dapur Pusat</p>
            </div>
        </div>
    </div>

    {{-- Navigasi Menu (BISA DI-SCROLL) --}}
    <nav class="flex-1 min-h-0 px-3 py-6 space-y-2 overflow-y-auto overflow-x-hidden [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-white/20 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-white/40">
        
        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-2 text-xs font-bold text-orange-300 uppercase tracking-widest whitespace-nowrap">
            Menu Utama
        </div>

        <a href="{{ route('pemasok.dashboard') }}" wire:navigate
            class="flex items-center px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ $isDashboardActive 
                ? 'bg-white text-[#EA580C] shadow-lg' 
                : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'" 
            title="Dashboard">
            <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ $isDashboardActive ? 'text-[#EA580C]' : 'text-orange-300 group-hover:text-white' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Dashboard</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-orange-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Operasional
        </div>

        {{-- Pesanan Masuk --}}
        <div x-data="{ pesananOpen: true }" class="mt-1">
            <button 
                @click="if(!sidebarOpen) sidebarOpen = true; pesananOpen = !pesananOpen"
                class="w-full flex items-center justify-between px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group
                {{ $isPesananActive ? 'bg-white/20 text-white' : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
                :class="sidebarOpen ? '' : 'justify-center'" title="Pesanan Masuk">
                <div class="flex items-center">
                    <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ $isPesananActive ? 'text-white' : 'text-orange-300 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span x-show="sidebarOpen" class="ml-3 whitespace-nowrap">Pesanan Masuk</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': pesananOpen}" class="w-4 h-4 transition-transform duration-300 {{ $isPesananActive ? 'text-white' : 'text-orange-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="pesananOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-2 space-y-1 px-2 border-l-2 border-white/20 ml-4">
                <a href="{{ route('pemasok.pesanan-masuk') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('pemasok.pesanan-masuk') ? 'text-[#EA580C] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-orange-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Daftar Pesanan
                </a>
                <a href="{{ route('pemasok.riwayat-produksi') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('pemasok.riwayat-produksi') ? 'text-[#EA580C] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-orange-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Riwayat Produksi
                </a>
            </div>
        </div>

        {{-- Pengiriman Logistik --}}
        <a href="{{ route('pemasok.pengiriman') }}" wire:navigate
            class="flex items-center px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('pemasok.pengiriman') 
                ? 'bg-white text-[#EA580C] shadow-lg' 
                : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'" title="Pengiriman & Logistik">
            <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ request()->routeIs('pemasok.pengiriman') ? 'text-[#EA580C]' : 'text-orange-300 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Pengiriman Logistik</span>
        </a>

        {{-- Manajemen Return --}}
        {{-- Manajemen Return --}}
        <a href="{{ route('pemasok.manajemen-return') }}" wire:navigate
            class="flex items-center px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('pemasok.manajemen-return') 
                ? 'bg-white text-[#EA580C] shadow-lg' 
                : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'" title="Manajemen Return">
            
            <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ request()->routeIs('pemasok.manajemen-return') ? 'text-[#EA580C]' : 'text-orange-300 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
            </svg>
            
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Manajemen Return</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-orange-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Keuangan
        </div>

        {{-- Dompet & Saldo --}}
        <div x-data="{ keuanganOpen: true }" class="mt-1">
            <button 
                @click="if(!sidebarOpen) sidebarOpen = true; keuanganOpen = !keuanganOpen"
                class="w-full flex items-center justify-between px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group
                {{ $isKeuanganActive ? 'bg-white/20 text-white' : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
                :class="sidebarOpen ? '' : 'justify-center'" title="Dompet & Saldo">
                <div class="flex items-center">
                    <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ $isKeuanganActive ? 'text-white' : 'text-orange-300 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span x-show="sidebarOpen" class="ml-3 whitespace-nowrap">Dompet & Saldo</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': keuanganOpen}" class="w-4 h-4 transition-transform duration-300 {{ $isKeuanganActive ? 'text-white' : 'text-orange-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="keuanganOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-2 space-y-1 px-2 border-l-2 border-white/20 ml-4">
                <a href="#" class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all text-orange-100 hover:text-white hover:bg-white/10 font-semibold">
                    Informasi Saldo
                </a>
                <a href="{{ route('pemasok.tarik-dana') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('pemasok.tarik-dana') ? 'text-[#EA580C] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-orange-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Tarik Dana (Withdraw)
                </a>
            </div>
        </div>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-orange-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Inventory
        </div>

        {{-- Stok & Produk --}}
        <a href="{{ route('pemasok.inventaris') }}" wire:navigate
            class="flex items-center px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('pemasok.inventaris') 
                ? 'bg-white text-[#EA580C] shadow-lg' 
                : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'" title="Stok & Produk">
            <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ request()->routeIs('pemasok.inventaris') ? 'text-[#EA580C]' : 'text-orange-300 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Stok & Produk</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-orange-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Laporan
        </div>

        {{-- Laporan & Analitik --}}
        <a href="{{ route('pemasok.laporan') }}" wire:navigate
            class="flex items-center px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('pemasok.laporan') 
                ? 'bg-white text-[#EA580C] shadow-lg' 
                : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'" title="Laporan & Analitik">
            <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ request()->routeIs('pemasok.laporan') ? 'text-[#EA580C]' : 'text-orange-300 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Laporan & Analitik</span>
        </a>

        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-orange-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Akun & Pengaturan
        </div>

        {{-- Pengaturan Profil --}}
        <a href="{{ route('pemasok.profil') }}" wire:navigate
            class="flex items-center px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('pemasok.profil') 
                ? 'bg-white text-[#EA580C] shadow-lg' 
                : 'text-orange-100 hover:bg-white/10 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'" title="Pengaturan Profil">
            <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ request()->routeIs('pemasok.profil') ? 'text-[#EA580C]' : 'text-orange-300 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Pengaturan Profil</span>
        </a>

        <div class="h-6"></div>
    </nav>

    {{-- User Profile & Logout (PASTI MUNCUL) --}}
    <div class="p-4 border-t border-white/10 bg-black/20 shrink-0">
        <div class="flex items-center gap-3 mb-4 px-1" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="h-10 w-10 flex-shrink-0 rounded-full bg-white flex items-center justify-center text-[#EA580C] font-extrabold text-base shadow-md border-2 border-transparent relative">
                {{ substr(Auth::user()->name ?? 'P', 0, 2) }}
                <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 rounded-full border-2 border-[#EA580C]"></span>
            </div>
            
            <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                <p class="text-[15px] font-extrabold text-white truncate">{{ Auth::user()->name ?? 'Pemasok' }}</p>
                <p class="text-xs text-orange-200 font-medium truncate w-32">{{ Auth::user()->email ?? 'pemasok@scfs.com' }}</p>
            </div>
        </div>

        <button wire:click="logout" 
                class="w-full flex items-center px-3 py-3 text-[15px] font-bold text-white bg-rose-500/80 border border-rose-400/50 rounded-xl hover:bg-rose-500 transition-all duration-300 shadow-sm focus:outline-none"
                :class="sidebarOpen ? 'justify-center' : 'justify-center p-2 bg-transparent border-0 shadow-none hover:bg-white/10 hover:text-rose-400'"
                title="Keluar">
            <svg class="w-5 h-5 flex-shrink-0" :class="sidebarOpen ? 'mr-2.5' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span x-show="sidebarOpen" x-transition>Keluar Sistem</span>
        </button>
    </div>

</aside>