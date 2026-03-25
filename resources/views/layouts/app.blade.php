<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        
        {{-- [PERBAIKAN] Tambahkan 'pemasok' ke dalam array pengecekan --}}
        @if(in_array(Auth::user()->role, ['admin', 'merchant', 'pemasok']))
            
            {{-- ========================================== --}}
            {{-- LAYOUT KHUSUS DASHBOARD BERSIDEBAR         --}}
            {{-- ========================================== --}}
            
            <div x-data="{ sidebarOpen: true }" class="flex h-screen overflow-hidden">
                
                {{-- 1. SIDEBAR DINAMIS (Baca status sidebarOpen) --}}
                @if(Auth::user()->role === 'admin')
                    <livewire:layout.admin-sidebar />
                @elseif(Auth::user()->role === 'merchant')
                    <livewire:layout.merchant-sidebar />
                @elseif(Auth::user()->role === 'pemasok')
                    {{-- [PERBAIKAN] Panggil komponen sidebar pemasok yang baru kita buat --}}
                    <livewire:layout.pemasok-sidebar />
                @endif
                

                {{-- 2. KONTEN UTAMA (KANAN) --}}
                <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden bg-gray-50 transition-all duration-300">
                    
                    {{-- Header Mobile (Hanya muncul di HP) --}}
                    <header class="bg-white shadow-sm flex items-center justify-between px-6 py-4 md:hidden sticky top-0 z-20">
                        <span class="font-bold text-lg text-gray-800">
                            @if(Auth::user()->role === 'admin')
                                Admin Panel
                            @elseif(Auth::user()->role === 'merchant')
                                Mitra Kantin
                            @elseif(Auth::user()->role === 'pemasok')
                                Dapur Pusat
                            @endif
                        </span>
                        
                        {{-- Tombol Buka Tutup Sidebar di HP --}}
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-orange-600 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                            </svg>
                        </button>
                    </header>

                    {{-- Isi Halaman (Slot Dashboard Pemasok akan masuk ke sini) --}}
                    <main class="w-full h-full">
                        {{ $slot }}
                    </main>
                </div>
            </div>

        @else
            
            {{-- ========================================== --}}
            {{-- LAYOUT STANDAR (MAHASISWA/UMUM LAINNYA)    --}}
            {{-- ========================================== --}}
            <div class="min-h-screen bg-gray-100">
                <livewire:layout.navigation />

                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main>
                    {{ $slot }}
                </main>
            </div>

        @endif
        
    </body>
</html>