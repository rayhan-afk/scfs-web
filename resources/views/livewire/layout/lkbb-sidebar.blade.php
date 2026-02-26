<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $openMasterData  = false;
    public bool $openOperasional = false;
    public bool $openKeuangan    = false;

    public function mount(): void
    {
        $this->openMasterData  = request()->is('keuangan/mahasiswa*', 'keuangan/merchant*', 'keuangan/pemasok*');
        $this->openOperasional = request()->routeIs('supply-chain.*');
        $this->openKeuangan    = request()->is('keuangan/pencairan*', 'keuangan/penagihan*') || request()->routeIs('lkbb.wallets');
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<aside
    x-data="{
        masterData:  @entangle('openMasterData'),
        operasional: @entangle('openOperasional'),
        keuangan:    @entangle('openKeuangan'),
    }"
    class="w-60 bg-white border-r border-gray-100 hidden md:flex flex-col h-screen fixed sticky top-0 z-30"
    style="font-family: 'Inter', sans-serif;"
>

    {{-- ══════════════════════════════════════
         LOGO
    ══════════════════════════════════════ --}}
    <div class="flex items-center gap-3 h-16 px-5 border-b border-gray-100 shrink-0">
        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
            </svg>
        </div>
        <div class="leading-tight">
            <p class="text-sm font-bold text-gray-900">SCFS ADMIN</p>
            <p class="text-[10px] text-gray-400">LAPI ITB PANEL</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════
         NAVIGASI
    ══════════════════════════════════════ --}}
    <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-0.5 text-sm">

        @php
            /* Helper classes */
            $linkBase  = 'flex items-center gap-2.5 px-2.5 py-2 rounded-lg transition-colors duration-150 w-full text-left';
            $linkOn    = 'bg-blue-50 text-blue-600 font-semibold';
            $linkOff   = 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium';
            $subLinkOn = 'bg-blue-50 text-blue-600 font-semibold';
            $subLinkOf = 'text-gray-500 hover:bg-gray-50 hover:text-gray-800 font-medium';
            $icon      = 'w-4 h-4 shrink-0';
            $subIcon   = 'w-3.5 h-3.5 shrink-0';
            $label     = 'text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-2.5 pt-4 pb-1 block';
        @endphp

        {{-- ─── MENU UTAMA ─── --}}
        <span class="{{ $label }}">Menu Utama</span>

        <a href="{{ route('dashboard') }}" wire:navigate
           class="{{ $linkBase }} {{ request()->routeIs('dashboard','lkbb.dashboard') ? $linkOn : $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Dashboard
        </a>

        {{-- ─── VERIFIKASI & APPROVAL ─── --}}
        <span class="{{ $label }}">Verifikasi & Approval</span>

        <a href="/approval/mahasiswa"
           class="{{ $linkBase }} {{ request()->is('approval/mahasiswa*') ? $linkOn : $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Verifikasi Mahasiswa
        </a>

        <a href="{{ route('approval.merchant') }}"
           class="{{ $linkBase }} {{ request()->routeIs('approval.merchant') ? $linkOn : $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Verifikasi Merchant
        </a>

        <a href="{{ route('supply-chain.approval') }}"
           class="{{ $linkBase }} {{ request()->routeIs('supply-chain.approval') ? $linkOn : $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Verifikasi Pemasok
        </a>
        

        {{-- ─── MASTER DATA (collapsible) ─── --}}
        <span class="{{ $label }}">Master Dompet</span>

        <button @click="masterData = !masterData"
                class="{{ $linkBase }} {{ request()->is('keuangan/mahasiswa*','keuangan/merchant*','keuangan/pemasok*') ? $linkOn : $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="flex-1 text-left">Dompet</span>
            <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-200" :class="masterData && 'rotate-180'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="masterData" x-collapse class="space-y-0.5 pl-5">
            <a href="/keuangan/mahasiswa"
               class="{{ $linkBase }} {{ request()->is('keuangan/mahasiswa*') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Dompet Mahasiswa
            </a>
            <a href="/keuangan/merchant"
               class="{{ $linkBase }} {{ request()->is('keuangan/merchant*') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Dompet Merchant
            </a>
            <a href="/keuangan/pemasok"
               class="{{ $linkBase }} {{ request()->is('keuangan/pemasok*') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
                Dompet Pemasok
            </a>
        </div>

        {{-- ─── OPERASIONAL (collapsible) ─── --}}
        <span class="{{ $label }}">Operasional</span>

        <button @click="operasional = !operasional"
                class="{{ $linkBase }} {{ request()->routeIs('supply-chain.*') && !request()->routeIs('supply-chain.approval') ? $linkOn : $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="flex-1 text-left">Operasional Transaksi</span>
            <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-200" :class="operasional && 'rotate-180'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="operasional" x-collapse class="space-y-0.5 pl-5">
            <a href="{{ route('supply-chain.create') }}"
               class="{{ $linkBase }} {{ request()->routeIs('supply-chain.create') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
                </svg>
                Pengajuan Rantai Pasok
            </a>
            <a href="{{ route('supply-chain.bills') }}"
               class="{{ $linkBase }} {{ request()->routeIs('supply-chain.bills') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                Tagihan Merchant
            </a>
            <a href="#" class="{{ $linkBase }} {{ $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Transaksi
            </a>
        </div>

        {{-- ─── KEUANGAN (collapsible) ─── --}}
        <span class="{{ $label }}">Keuangan</span>

        <button @click="keuangan = !keuangan"
                class="{{ $linkBase }} {{ request()->is('keuangan/pencairan*','keuangan/penagihan*') || request()->routeIs('lkbb.wallets') ? $linkOn : $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="flex-1 text-left">Keuangan & Settlement</span>
            <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-200" :class="keuangan && 'rotate-180'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="keuangan" x-collapse class="space-y-0.5 pl-5">
            <a href="{{ route('lkbb.wallets') }}" wire:navigate
               class="{{ $linkBase }} {{ request()->routeIs('lkbb.wallets') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Saldo & Wallet
            </a>
            <a href="/keuangan/pencairan"
               class="{{ $linkBase }} {{ request()->is('keuangan/pencairan*') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Pencairan Dana
            </a>
            <a href="/keuangan/penagihan"
               class="{{ $linkBase }} {{ request()->is('keuangan/penagihan*') ? $subLinkOn : $subLinkOf }}">
                <svg class="{{ $subIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Penagihan Tunai
            </a>
        </div>
         {{-- ─── LAPORAN ─── --}}
        <span class="{{ $label }}">Laporan</span>

        <a href="#" class="{{ $linkBase }} {{ $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Laporan Keuangan
        </a>

        <a href="#" class="{{ $linkBase }} {{ $linkOff }}">
            <svg class="{{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Laporan Transaksi
        </a>

    </nav>

    {{-- ══════════════════════════════════════
         PROFILE
    ══════════════════════════════════════ --}}
    <div class="p-3 border-t border-gray-100 shrink-0">
        <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl hover:bg-gray-50 transition cursor-pointer">
            <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold text-sm border border-orange-200 shrink-0">
                {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
            </div>
            <div class="overflow-hidden flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate leading-tight">{{ Auth::user()->name ?? 'Admin User' }}</p>
                <p class="text-[11px] text-gray-400 leading-tight">Super Admin</p>
            </div>
            <button wire:click="logout" title="Sign Out"
                    class="shrink-0 text-gray-300 hover:text-red-500 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </div>
    </div>

</aside>