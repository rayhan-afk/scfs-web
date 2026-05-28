<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

new
#[Layout('layouts.app')]
class extends Component {

    /**
     * Resolve profile berdasar role + return status_verifikasi + catatan_penolakan.
     * Unified untuk merchant & pemasok.
     */
    #[Computed]
    public function profile()
    {
        $user = Auth::user();
        return match ($user->role) {
            'merchant' => $user->merchantProfile,
            'pemasok'  => $user->pemasokProfile,
            default    => null,
        };
    }

    #[Computed]
    public function status(): string
    {
        return $this->profile?->status_verifikasi ?? 'belum_melengkapi';
    }

    #[Computed]
    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }

    #[Computed]
    public function isPending(): bool
    {
        return in_array($this->status, ['menunggu_review', 'menunggu', 'pending'], true);
    }

    #[Computed]
    public function dashboardRoute(): ?string
    {
        return match (Auth::user()->role) {
            'merchant' => 'merchant.dashboard',
            'pemasok'  => 'pemasok.dashboard',
            default    => null,
        };
    }
}; ?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center p-6">
<div class="w-full max-w-2xl">

    @php
        $status = $this->status;
        $isRejected = $this->isRejected;
        $isPending = $this->isPending;
        $profile = $this->profile;

        // Tematik per state — 2 state: menunggu vs ditolak
        $theme = $isRejected
            ? ['from' => 'from-rose-500', 'to' => 'to-red-600', 'badge' => 'bg-rose-100 text-rose-700 border-rose-200', 'shadow' => 'shadow-rose-200']
            : ['from' => 'from-amber-500', 'to' => 'to-orange-600', 'badge' => 'bg-amber-100 text-amber-700 border-amber-200', 'shadow' => 'shadow-amber-200'];
    @endphp

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">

        {{-- HERO ICON --}}
        <div class="relative px-8 pt-12 pb-8 text-center bg-gradient-to-br from-gray-50 to-white">

            {{-- Big SVG illustration --}}
            <div class="mx-auto mb-6 relative w-32 h-32">
                <div class="absolute inset-0 bg-gradient-to-br {{ $theme['from'] }} {{ $theme['to'] }} rounded-full opacity-10 blur-2xl"></div>
                <div class="relative w-full h-full rounded-3xl bg-gradient-to-br {{ $theme['from'] }} {{ $theme['to'] }} flex items-center justify-center shadow-xl {{ $theme['shadow'] }}">

                    @if($isRejected)
                        {{-- X circle illustration --}}
                        <svg class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @else
                        {{-- Clock/hourglass illustration --}}
                        <svg class="w-16 h-16 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @endif
                </div>
            </div>

            {{-- Status badge --}}
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $theme['badge'] }} mb-3">
                @if($isRejected)
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Pengajuan Ditolak
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Menunggu Verifikasi
                @endif
            </span>

            {{-- Heading --}}
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mb-2">
                @if($isRejected)
                    Pengajuan Anda Ditolak
                @else
                    Akun Anda Sedang Menunggu Verifikasi
                @endif
            </h1>

            {{-- Sub-message --}}
            <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                @if($isRejected)
                    Tim LKBB telah meninjau pengajuan Anda dan ada beberapa hal yang perlu direvisi. Silakan baca catatan di bawah, lalu perbarui data di dashboard dan ajukan ulang.
                @else
                    Data Anda sudah terkirim. Tim LKBB sedang meninjau profil dan dokumen Anda. Proses biasanya memakan waktu <span class="font-bold text-gray-700">1–3 hari kerja</span>. Anda akan dinotifikasi setelah disetujui. Menu bisnis akan terbuka otomatis setelah ACC.
                @endif
            </p>
        </div>

        {{-- BODY: catatan / checklist --}}
        <div class="px-8 pb-8 space-y-5">

            {{-- Catatan penolakan (kalau ditolak) --}}
            @if($isRejected && $profile?->catatan_penolakan)
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-widest text-rose-700 mb-1">Catatan dari LKBB</p>
                            <p class="text-sm text-rose-800 leading-relaxed font-medium">{{ $profile->catatan_penolakan }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Info card untuk pending review --}}
            @if($isPending && !$isRejected)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-700 mb-3">Yang Sedang Ditinjau</p>
                    <ul class="space-y-2 text-sm text-amber-900">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Kelengkapan data profil & dokumen pendukung (KTP, foto usaha)</span>
                        </li>
                        @if(Auth::user()->role === 'merchant')
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Usulan bagi hasil yang Anda ajukan untuk LKBB</span>
                            </li>
                        @endif
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Validasi nomor rekening tujuan pencairan dana</span>
                        </li>
                    </ul>
                </div>
            @endif

            {{-- ACTION BUTTONS --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                @if($this->dashboardRoute)
                    <a href="{{ route($this->dashboardRoute) }}" wire:navigate
                       class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r {{ $theme['from'] }} {{ $theme['to'] }} text-white font-black text-sm rounded-2xl shadow-lg {{ $theme['shadow'] }} hover:opacity-95 active:scale-[0.98] transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        {{ $isRejected ? 'Revisi Data di Dashboard' : 'Kembali ke Dashboard' }}
                    </a>
                @endif

                <button type="button" onclick="window.location.reload()"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-2xl hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Cek Status
                </button>
            </div>

            {{-- Help link --}}
            <div class="text-center pt-3 border-t border-gray-100">
                <p class="text-[11px] text-gray-400 font-medium">
                    Butuh bantuan? Hubungi admin LKBB di
                    <a href="mailto:admin@scfs.id" class="font-bold text-indigo-600 hover:text-indigo-700 hover:underline">admin@scfs.id</a>
                </p>
            </div>
        </div>
    </div>
</div>
</div>
