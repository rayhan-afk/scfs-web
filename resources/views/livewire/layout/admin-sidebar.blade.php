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

<aside class="w-72 bg-white border-r border-gray-200 min-h-screen flex flex-col transition-all duration-300 hidden md:flex">
    
    <div class="h-20 flex items-center px-8 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-50 rounded-lg">
                <img src="{{ asset('images/logo-lapi.png') }}" alt="SCFS" class="h-8 w-auto">
            </div>
            <div>
                <h1 class="font-bold text-gray-800 text-lg tracking-wide">SCFS ADMIN</h1>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">LAPI ITB Panel</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        
        <div class="px-4 mb-2 text-xs font-bold text-gray-400 uppercase tracking-wider">
            Menu Utama
        </div>

        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group
           {{ request()->routeIs('dashboard') 
               ? 'bg-blue-50 text-blue-700' 
               : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            Dashboard
        </a>

        <a href="#" 
           class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group
           text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Verifikasi Mahasiswa
            <span class="ml-auto bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded-full border border-red-200">3</span>
        </a>

        <a href="#" 
           class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group
           text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
            </svg>
            Monitoring Transaksi
        </a>

        <div class="px-4 mt-6 mb-2 text-xs font-bold text-gray-400 uppercase tracking-wider">
            Keuangan
        </div>

        <a href="#" 
           class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group
           text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Keuangan Pemasok
        </a>

    </nav>

    <div class="p-4 border-t border-gray-100 bg-gray-50/50">
        <div class="flex items-center gap-3 mb-4 px-2">
            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm shadow-sm border border-blue-200">
                {{ substr(Auth::user()->name, 0, 2) }}
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <button wire:click="logout" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-100 rounded-lg hover:bg-red-50 hover:border-red-200 transition-all duration-200 shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Keluar Aplikasi
        </button>
    </div>

</aside>