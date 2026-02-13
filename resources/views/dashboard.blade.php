<x-app-layout>
    
    {{-- HEADER (Hanya muncul untuk Mahasiswa/Merchant, Admin punya header sendiri di dalam komponen) --}}
    @if(Auth::user()->role !== 'admin')
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }} {{ ucfirst(Auth::user()->role) }}
            </h2>
        </x-slot>
    @endif

    <div class="py-12">
        
        {{-- ========================================== --}}
        {{-- 1. KHUSUS ADMIN (FULL WIDTH & MODERN)      --}}
        {{-- ========================================== --}}
        @if(Auth::user()->role === 'admin')
            
            {{-- Wrapper Admin: W-FULL (Full Lebar) & PX-8 (Jarak dari sidebar) --}}
            <div class="w-full px-6 md:px-8 space-y-6">
                <livewire:dashboard.admin />
            </div>

        {{-- ========================================== --}}
        {{-- 2. KHUSUS MAHASISWA (CENTERED LAYOUT)      --}}
        {{-- ========================================== --}}
        @elseif(Auth::user()->role === 'mahasiswa')
            
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <livewire:layout.wallet-card />
                <livewire:layout.product-list />
                <livewire:layout.transaction-history />
            </div>

        {{-- ========================================== --}}
        {{-- 3. KHUSUS MERCHANT (CENTERED LAYOUT)       --}}
        {{-- ========================================== --}}
        @elseif(Auth::user()->role === 'merchant')

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-orange-500">
                    <h3 class="text-lg font-bold text-gray-900">Halo, Mitra Kantin! 👋</h3>
                    <p class="text-gray-600 mt-2">
                        Halaman kelola produk dan laporan penjualan sedang disiapkan.
                    </p>
                </div>
            </div>

        @endif

    </div>
</x-app-layout>