<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        
        @if(Auth::user()->role === 'admin')
            
            {{-- ========================================== --}}
            {{-- LAYOUT KHUSUS ADMIN (DENGAN LOGIKA BUKA TUTUP) --}}
            {{-- ========================================== --}}
            
            {{-- Kita pasang x-data di sini agar Sidebar & Header bisa saling komunikasi --}}
            <div x-data="{ sidebarOpen: true }" class="flex h-screen overflow-hidden">
                
                {{-- 1. SIDEBAR (Akan baca status sidebarOpen) --}}
                <livewire:layout.admin-sidebar />

                {{-- 2. KONTEN UTAMA (KANAN) --}}
                <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden bg-gray-50 transition-all duration-300">
                    
                    {{-- Header Mobile (Hanya muncul di HP) --}}
                    <header class="bg-white shadow-sm flex items-center justify-between px-6 py-4 md:hidden sticky top-0 z-20">
                        <span class="font-bold text-lg text-gray-800">Admin Panel</span>
                        
                        {{-- Tombol Buka Tutup Sidebar di HP --}}
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-blue-600 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                            </svg>
                        </button>
                    </header>

                    {{-- Isi Halaman (Slot) --}}
                    <main class="w-full h-full">
                        {{ $slot }}
                    </main>
                </div>
            </div>

        @else
            
            {{-- ========================================== --}}
            {{-- LAYOUT STANDAR (MAHASISWA/MERCHANT)        --}}
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