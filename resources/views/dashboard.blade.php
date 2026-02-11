<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <livewire:layout.wallet-card />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Menu Kantin Tersedia</h3>
                <p class="text-gray-500">Daftar makanan akan muncul di sini nanti...</p>
            </div>

        </div>
    </div>
</x-app-layout>