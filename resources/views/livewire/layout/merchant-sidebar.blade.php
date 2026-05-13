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
    // Logika deteksi menu aktif untuk Merchant
    $isPasokActive = request()->routeIs('merchant.order', 'merchant.penerimaan');
    $isJualActive = request()->routeIs('merchant.scan', 'merchant.riwayat');
    $isKasActive = request()->routeIs('merchant.withdraw', 'merchant.setoran');
    $isPengaturanActive = request()->routeIs('merchant.katalog', 'merchant.profile');
@endphp

<aside 
    x-data="{ sidebarOpen: true }"
    :class="sidebarOpen ? 'w-72' : 'w-20'"
    class="bg-[#059669] border-r border-[#059669] h-screen flex flex-col transition-all duration-300 ease-in-out relative hidden md:flex z-50 shadow-2xl text-white"
>
    
    {{-- Toggle Button --}}
    <button 
        @click="sidebarOpen = !sidebarOpen"
        class="absolute -right-3.5 top-9 bg-white border-2 border-[#059669] text-[#059669] rounded-full p-1.5 shadow-md hover:bg-gray-50 hover:scale-110 transition-all z-50 focus:outline-none"
    >
        <svg x-show="sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
        <svg x-show="!sidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
    </button>

    {{-- Logo Area --}}
    <div class="h-20 flex items-center px-4 border-b border-white/10 overflow-hidden whitespace-nowrap bg-black/10 shrink-0">
        <div class="flex items-center gap-3 transition-all duration-300">
            <div class="p-2 bg-white rounded-xl flex-shrink-0 shadow-sm flex items-center justify-center w-10 h-10">
                <svg class="w-6 h-6 text-[#059669]" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            
            <div x-show="sidebarOpen" x-transition.opacity.duration.300ms class="transition-opacity">
                <h1 class="font-black text-white text-xl tracking-tight">MITRA KANTIN</h1>
                <p class="text-xs text-emerald-200 uppercase tracking-widest font-bold">SCFS Ecosystem</p>
            </div>
        </div>
    </div>

    {{-- Navigasi Menu --}}
    <nav class="flex-1 min-h-0 px-3 py-6 space-y-2 overflow-y-auto overflow-x-hidden [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-white/20 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-white/40">
        
        {{-- 1. BERANDA --}}
        <div x-show="sidebarOpen" x-transition class="px-4 mb-2 mt-2 text-xs font-bold text-emerald-300 uppercase tracking-widest whitespace-nowrap">
            Menu Utama
        </div>

        <a href="{{ route('merchant.dashboard') }}" wire:navigate
            class="flex items-center px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group whitespace-nowrap
            {{ request()->routeIs('merchant.dashboard') 
                ? 'bg-white text-[#059669] shadow-lg' 
                : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'" 
            title="Beranda Toko">

            <svg class="w-6 h-6 flex-shrink-0 transition-colors
                {{ request()->routeIs('merchant.dashboard') 
                    ? 'text-[#059669]' 
                    : 'text-emerald-300 group-hover:text-white' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span x-show="sidebarOpen" x-transition class="ml-3 transition-opacity duration-300">Beranda Toko</span>
        </a>

        {{-- 2. RANTAI PASOK --}}
        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-emerald-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Rantai Pasok (Bahan)
        </div>

        <div x-data="{ pasokOpen: true }" class="mt-1">
            <button 
                @click="if(!sidebarOpen) sidebarOpen = true; pasokOpen = !pasokOpen"
                class="w-full flex items-center justify-between px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group
                {{ $isPasokActive ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}"
                :class="sidebarOpen ? '' : 'justify-center'" title="Rantai Pasok">
                <div class="flex items-center">
                    <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ $isPasokActive ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span x-show="sidebarOpen" class="ml-3 whitespace-nowrap">Manajemen Bahan</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': pasokOpen}" class="w-4 h-4 transition-transform duration-300 {{ $isPasokActive ? 'text-white' : 'text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="pasokOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-2 space-y-1 px-2 border-l-2 border-white/20 ml-4">
                <a href="{{ route('merchant.order') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.order') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Order Bahan Baku
                </a>
                <a href="{{ route('merchant.penerimaan') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.penerimaan') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Penerimaan Barang
                </a>
            </div>
        </div>

        {{-- 3. AKTIVITAS PENJUALAN --}}
        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-emerald-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Aktivitas Penjualan
        </div>

        <div x-data="{ jualOpen: true }" class="mt-1">
            <button 
                @click="if(!sidebarOpen) sidebarOpen = true; jualOpen = !jualOpen"
                class="w-full flex items-center justify-between px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group
                {{ $isJualActive ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}"
                :class="sidebarOpen ? '' : 'justify-center'" title="Penjualan">
                <div class="flex items-center">
                    <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ $isJualActive ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span x-show="sidebarOpen" class="ml-3 whitespace-nowrap">Penjualan Toko</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': jualOpen}" class="w-4 h-4 transition-transform duration-300 {{ $isJualActive ? 'text-white' : 'text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div x-show="jualOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-2 space-y-1 px-2 border-l-2 border-white/20 ml-4">
                <a href="{{ route('merchant.scan') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.scan') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Mesin Kasir (POS)
                </a>
                <a href="{{ route('merchant.riwayat') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.riwayat') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Riwayat Penjualan
                </a>
            </div>
        </div>

        {{-- 4. ARUS KAS & SETORAN --}}
        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-emerald-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Keuangan
        </div>

        <div x-data="{ kasOpen: true }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; kasOpen = !kasOpen" 
                    class="w-full flex items-center justify-between px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group {{ $isKasActive ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Arus Kas">
                <div class="flex items-center">
                    <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ $isKasActive ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-show="sidebarOpen" class="ml-3 whitespace-nowrap">Arus Kas</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': kasOpen}" class="w-4 h-4 transition-transform duration-300 {{ $isKasActive ? 'text-white' : 'text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="kasOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-2 space-y-1 px-2 border-l-2 border-white/20 ml-4">
                <a href="{{ route('merchant.withdraw') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.withdraw') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Tarik Saldo (Digital)
                </a>
                <a href="{{ route('merchant.setoran') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.setoran') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Tagihan Setoran (Tunai)
                </a>
            </div>
        </div>

        {{-- 5. MANAJEMEN TOKO --}}
        <div x-show="sidebarOpen" x-transition class="px-4 mb-1 mt-6 text-xs font-bold text-emerald-300 uppercase tracking-widest whitespace-nowrap border-t border-white/10 pt-4">
            Pengaturan
        </div>

        <div x-data="{ pengaturanOpen: true }" class="mt-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; pengaturanOpen = !pengaturanOpen" 
                    class="w-full flex items-center justify-between px-3 py-3 text-[15px] font-bold rounded-xl transition-all duration-200 group {{ $isPengaturanActive ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}"
                    :class="sidebarOpen ? '' : 'justify-center'" title="Pengaturan Toko">
                <div class="flex items-center">
                    <svg class="w-6 h-6 flex-shrink-0 transition-colors {{ $isPengaturanActive ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span x-show="sidebarOpen" class="ml-3 whitespace-nowrap">Pengaturan Toko</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-180': pengaturanOpen}" class="w-4 h-4 transition-transform duration-300 {{ $isPengaturanActive ? 'text-white' : 'text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="pengaturanOpen && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-2 space-y-1 px-2 border-l-2 border-white/20 ml-4">
                <a href="{{ route('merchant.katalog') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.katalog') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Katalog Jualan
                </a>
                <a href="{{ route('merchant.profile') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('merchant.profile') ? 'text-[#059669] bg-white font-extrabold border-l-4 border-yellow-400 -ml-[2px]' : 'text-emerald-100 hover:text-white hover:bg-white/10 font-semibold' }}">
                    Profil & Rekening
                </a>
            </div>
        </div>

        <div class="h-6"></div>
    </nav>

    {{-- User Profile & Logout (PASTI MUNCUL) --}}
    <div class="p-4 border-t border-white/10 bg-black/20 shrink-0">
        <div class="flex items-center gap-3 mb-4 px-1" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="h-10 w-10 flex-shrink-0 rounded-full bg-white flex items-center justify-center text-[#059669] font-extrabold text-base shadow-md border-2 border-transparent relative">
                {{ substr(Auth::user()->name ?? 'M', 0, 2) }}
                <span class="absolute bottom-0 right-0 w-3 h-3 bg-yellow-400 rounded-full border-2 border-[#059669]"></span>
            </div>
            
            <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                <p class="text-[15px] font-extrabold text-white truncate">{{ Auth::user()->name ?? 'Kantin Merchant' }}</p>
                <p class="text-xs text-emerald-200 font-medium truncate w-32">{{ Auth::user()->email ?? 'kantin@scfs.com' }}</p>
            </div>
        </div>

        <button wire:click="logout" 
                class="w-full flex items-center px-3 py-3 text-[15px] font-bold text-white bg-rose-500/80 border border-rose-400/50 rounded-xl hover:bg-rose-500 transition-all duration-300 shadow-sm focus:outline-none"
                :class="sidebarOpen ? 'justify-center' : 'justify-center p-2 bg-transparent border-0 shadow-none hover:bg-white/10 hover:text-rose-400'"
                title="Keluar">
            <svg class="w-5 h-5 flex-shrink-0" :class="sidebarOpen ? 'mr-2.5' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span x-show="sidebarOpen" x-transition>Keluar Aplikasi</span>
        </button>
    </div>

</aside>