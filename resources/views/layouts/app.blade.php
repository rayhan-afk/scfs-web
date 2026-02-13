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
            
            {{-- LAYOUT KHUSUS ADMIN (SIDEBAR KIRI) --}}
            <div class="flex h-screen overflow-hidden">
                
                <livewire:layout.admin-sidebar />

                <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                    
                    <header class="bg-white shadow-sm flex items-center justify-between px-6 py-4 md:hidden">
                        <span class="font-bold text-lg">Admin Panel</span>
                        </header>

                    <main class="p-6">
                        {{ $slot }}
                    </main>
                </div>
            </div>

        @else
            
            {{-- LAYOUT STANDAR (MAHASISWA/MERCHANT) --}}
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