<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LKBB System') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-50" x-data="{ sidebarOpen: false }"> 
    <div class="flex h-screen overflow-hidden">
        
        <div class="hidden md:block">
             <livewire:layout.lkbb-sidebar />
        </div>

        <div x-show="sidebarOpen" class="fixed inset-0 z-40 flex md:hidden" role="dialog" aria-modal="true" style="display: none;">
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>
            
            <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Close sidebar</span>
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <livewire:layout.lkbb-sidebar />
            </div>
        </div>

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            
            <header class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-100 sticky top-0 z-20 shadow-sm">

                <div>
                    <h1 class="text-xl font-bold text-gray-800">
                        LKBB Command Center
                    </h1>

                    <p class="text-sm text-gray-400 mt-1">
                        Realtime monitoring ecosystem SCFS
                    </p>
                </div>

                <div class="flex items-center gap-4">

                    <x-notification-dropdown />

                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white flex items-center justify-center font-bold shadow-lg">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                </div>

            </header>
            <main class="w-full h-full p-6 md:p-8">
                {{ $slot }}
            </main>
            
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        window.Echo
            .private('App.Models.User.{{ auth()->id() }}')
            .notification((notification) => {

                const toast = document.createElement('div');

                toast.innerHTML = `
                    <div class="fixed top-5 right-5 z-[9999] w-[340px] bg-white border border-gray-100 shadow-2xl rounded-2xl p-4 animate-pulse">
                        <div class="flex gap-3 items-start">
                            
                            <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-xl">
                                🔔
                            </div>

                            <div class="flex-1">
                                <h3 class="font-bold text-gray-800 text-sm">
                                    ${notification.title}
                                </h3>

                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                    ${notification.message}
                                </p>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                    location.reload();
                }, 4000);
            });

    });
    </script>
</body>
</html>