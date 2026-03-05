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

<aside 
    :class="sidebarOpen ? 'w-72' : 'w-20'"
    class="bg-white border-r border-gray-200 min-h-screen flex-col transition-all duration-300 ease-in-out relative hidden md:flex z-40"
>
    {{-- Tombol Toggle Collapse --}}
    <button 
        @click="sidebarOpen = !sidebarOpen"
        class="absolute -right-3 top-9 bg-white border border-gray-200 text-gray-500 rounded-full p-1.5 shadow-sm hover:text-emerald-600 hover:border-emerald-300 transition-colors z-50 focus:outline-none"
    >
        <svg x-show="sidebarOpen" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
        <svg x-show="!sidebarOpen" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
    </button>

    {{-- Logo / Header Sidebar --}}
    <div class="h-20 flex items-center px-5 border-b border-gray-100 overflow-hidden whitespace-nowrap">
        <div class="flex items-center gap-3 transition-all duration-300">
            <div class="p-2.5 bg-gradient-to-br from-emerald-400 to-emerald-600 text-white rounded-xl flex-shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            
            <div x-show="sidebarOpen" x-transition.opacity.duration.300ms class="transition-opacity mt-1">
                <h1 class="font-extrabold text-gray-900 text-base tracking-wide leading-tight">MITRA KANTIN</h1>
                <p class="text-[9px] text-gray-400 uppercase tracking-widest font-bold">SCFS Ecosystem</p>
            </div>
        </div>
    </div>

    {{-- Navigasi Menu --}}
    <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto overflow-x-hidden scrollbar-hide">
        
        {{-- 1. BERANDA --}}
        <div x-show="sidebarOpen" x-transition class="px-3 mb-2 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Menu Utama
        </div>

        <a href="{{ route('merchant.dashboard') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.dashboard') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Beranda Toko">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.dashboard') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Beranda Toko</span>
        </a>

        {{-- 2. SUPPLY CHAIN (HULU) - BARU --}}
        <div x-show="sidebarOpen" x-transition class="px-3 mb-2 mt-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Rantai Pasok (Bahan)
        </div>

        <a href="{{ route('merchant.order') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.order') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Order Bahan Baku">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.order') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Order Bahan Baku</span>
        </a>

        <a href="{{ route('merchant.penerimaan') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.penerimaan') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Penerimaan Barang">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.penerimaan') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Penerimaan Barang</span>
        </a>

        {{-- 3. PENJUALAN (HILIR) --}}
        <div x-show="sidebarOpen" x-transition class="px-3 mb-2 mt-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Aktivitas Penjualan
        </div>

        <a href="{{ route('merchant.scan') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.scan') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Mesin Kasir (POS)">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.scan') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Mesin Kasir (POS)</span>
        </a>

        <a href="{{ route('merchant.riwayat') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.riwayat') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Riwayat Penjualan">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.riwayat') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Riwayat Penjualan</span>
        </a>

        {{-- 4. KEUANGAN & SETORAN --}}
        <div x-show="sidebarOpen" x-transition class="px-3 mb-2 mt-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Arus Kas & Setoran
        </div>

        <a href="{{ route('merchant.withdraw') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.withdraw') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Pencairan Dana (Digital)">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.withdraw') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Tarik Saldo (Digital)</span>
        </a>

        {{-- MENU YANG DIKEMBALIKAN SESUAI SOP NO 14 --}}
        <a href="{{ route('merchant.setoran') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.setoran') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Serah Terima Setoran Tunai">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.setoran') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Tagihan Setoran (Tunai)</span>
        </a>

        {{-- 5. MANAJEMEN TOKO --}}
        <div x-show="sidebarOpen" x-transition class="px-3 mb-2 mt-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            Pengaturan
        </div>

        <a href="{{ route('merchant.katalog') }}" wire:navigate
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.katalog') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Katalog Menu">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.katalog') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Katalog Jualan</span>
        </a>

        <a href="{{ route('merchant.profile') }}" wire:navigate {{-- Placeholder untuk route: merchant.profile --}}
           class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
           {{ request()->routeIs('merchant.profile') ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}"
           :class="sidebarOpen ? '' : 'justify-center'" title="Profil Toko">
            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('merchant.profile') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Profil & Rekening</span>
        </a>

        <div class="h-4"></div>
    </nav>

    {{-- User Profile & Logout Bottom Area --}}
    <div class="p-4 border-t border-gray-100 bg-gray-50/50">
        <div class="flex items-center gap-3 mb-4 px-1" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="h-10 w-10 flex-shrink-0 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center text-emerald-800 font-extrabold text-sm shadow-sm border border-emerald-300">
                {{ substr(Auth::user()->name, 0, 2) }}
            </div>
            
            <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                <p class="text-sm font-extrabold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] font-medium text-gray-500 truncate w-36">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <button wire:click="logout" 
                class="w-full flex items-center px-3 py-2.5 text-sm font-bold text-rose-600 bg-white border border-rose-100 rounded-xl hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 transition-all duration-200 shadow-sm"
                :class="sidebarOpen ? 'justify-center' : 'justify-center border-0 bg-transparent hover:bg-rose-50 shadow-none'"
                title="Keluar">
            <svg class="w-5 h-5 flex-shrink-0" :class="sidebarOpen ? 'mr-2' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span x-show="sidebarOpen" x-transition>Keluar Aplikasi</span>
        </button>
    </div>

</aside>