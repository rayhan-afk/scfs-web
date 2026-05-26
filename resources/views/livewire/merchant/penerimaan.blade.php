<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SupplyOrder;

new #[Layout('layouts.app')]
class extends Component {

    use WithPagination;

    // UI State
    public $statusFilter = 'aktif'; // 'aktif' (belum selesai), 'selesai', 'ditolak'
    public $search = '';

    // Modal Konfirmasi Terima
    public $showConfirmModal = false;
    public $selectedOrderId = null;

    // Modal Lihat Rincian (tampilkan detail PO terkonfirmasi/ditolak)
    public $showModalDetail = false;

    // Reset pagination saat filter berubah biar tidak nyangkut di halaman kosong
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function openConfirmModal($id)
    {
        $this->selectedOrderId = $id;
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal()
    {
        $this->showConfirmModal = false;
        $this->selectedOrderId = null;
    }

    public function bukaModalDetail($id)
    {
        $this->selectedOrderId = $id;
        $this->showModalDetail = true;
    }

    public function tutupModalDetail()
    {
        $this->showModalDetail = false;
        $this->selectedOrderId = null;
    }

    /**
     * ENGINE UTAMA: Mengambil daftar pesanan beserta detail barang dan info pengirim
     */
    #[Computed]
    public function supplyOrders()
    {
        // Eager loading ditambah 'pemasok.pemasokProfile' agar Merchant tahu siapa yang kirim
        $query = SupplyOrder::with(['details', 'pemasok.pemasokProfile'])
            ->withExists(['returns as has_active_return' => function ($q) {
                $q->whereIn('status', ['pending_supplier_review', 'approved', 'escalated_lkbb']);
            }])
            ->where('merchant_id', Auth::id());

        // Pencarian berdasarkan Nomor Order
        if ($this->search) {
            $query->where('nomor_order', 'like', '%' . trim($this->search) . '%');
        }

        // Filter Tab Aktif vs Selesai
        if ($this->statusFilter === 'aktif') {
            $query->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim', 'menunggu_pemasok']);
        } elseif ($this->statusFilter === 'selesai') {
            $query->where('status', 'selesai');
        } elseif ($this->statusFilter === 'ditolak') {
            $query->where('status', 'ditolak');
        }

        return $query->orderBy('updated_at', 'desc')->paginate(10);
    }

    /**
     * COMPUTED: PO yang sedang dibuka di modal detail.
     */
    #[Computed]
    public function selectedOrder()
    {
        if (! $this->selectedOrderId) {
            return null;
        }
        return SupplyOrder::with(['details', 'pemasok.pemasokProfile'])
            ->where('merchant_id', Auth::id())
            ->find($this->selectedOrderId);
    }

    #[Computed]
    public function selectedOrderEvents()
    {
        if (! $this->selectedOrder) {
            return collect();
        }
        return app(\App\Services\Tracking\TrackingTimelineService::class)
            ->buildEvents($this->selectedOrder);
    }

    #[Computed]
    public function selectedOrderProgress(): int
    {
        if (! $this->selectedOrder) {
            return 0;
        }
        return app(\App\Services\Tracking\TrackingTimelineService::class)
            ->progressPercentage($this->selectedOrder);
    }

    /**
     * CORE ACTION: Konfirmasi Penerimaan Barang Fisik
     */
    public function konfirmasiTerima()
    {
        if (!$this->selectedOrderId) return;

        try {
            DB::transaction(function () {
                $order = SupplyOrder::where('id', $this->selectedOrderId)
                            ->where('merchant_id', Auth::id())
                            ->lockForUpdate()
                            ->firstOrFail();

                if ($order->status !== 'dikirim') {
                    throw new \Exception('Aksi ditolak. Pesanan ini belum berstatus "Dikirim" oleh Pemasok.');
                }

                // 1. Eksekusi Perubahan Status PO Saja
                // Barang tidak otomatis masuk ke Layar Kasir, melainkan pindah ke "Gudang Bahan"
                $order->update([
                    'status' => 'selesai'
                ]);
                
            });

            $this->closeConfirmModal(); 
            session()->flash('success', 'Fisik barang berhasil diterima! Silakan cek "Gudang Bahan" di menu Katalog untuk meracik/memindahkannya ke Etalase Kasir.');

        } catch (\Exception $e) {
            $this->closeConfirmModal();
            session()->flash('error', $e->getMessage());
        }
    }

    #[Computed]
    public function stats(): array
    {
        $base = SupplyOrder::where('merchant_id', Auth::id());

        return [
            'aktif' => (clone $base)->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim', 'menunggu_pemasok'])->count(),
            'sedang_dikirim' => (clone $base)->where('status', 'dikirim')->count(),
            'diterima_bulan_ini' => (clone $base)
                ->where('status', 'selesai')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'nilai_aktif' => (clone $base)
                ->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim', 'menunggu_pemasok'])
                ->sum('total_estimasi'),
        ];
    }

    public function trackingFor(SupplyOrder $order): array
    {
        $svc = app(\App\Services\Tracking\TrackingTimelineService::class);
        return [
            'events' => $svc->buildEvents($order),
            'progress' => $svc->progressPercentage($order),
        ];
    }
}; ?>

<div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-6 lg:py-8">

    {{-- ===== Hero header (emerald) ===== --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-lime-500 p-6 sm:p-8 mb-6 shadow-[0_20px_50px_-20px_rgba(16,185,129,0.5)]">
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-10 -bottom-20 h-56 w-56 rounded-full bg-lime-300/30 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="text-white">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-100/90">Merchant · Penerimaan Logistik</p>
                <h1 class="mt-1 text-2xl sm:text-3xl font-black tracking-tight">Halo, {{ Auth::user()->name }} 👋</h1>
                <p class="mt-1 text-sm text-emerald-50/90 font-medium">Pantau pesanan, lacak posisi kurir, dan konfirmasi saat barang tiba di kantin Anda.</p>
            </div>
            <div class="relative w-full md:w-80">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor order / resi…"
                       class="w-full rounded-2xl border border-white/40 bg-white/95 backdrop-blur pl-11 pr-4 py-3 text-sm font-bold text-gray-700 placeholder:font-medium placeholder:text-gray-400 shadow-lg shadow-emerald-900/20 outline-none focus:ring-2 focus:ring-white">
                <svg class="absolute left-3.5 top-3.5 h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- ===== Stats row ===== --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <x-tracking.stat-tile
            label="Pesanan Aktif"
            :value="$this->stats['aktif']"
            caption="sedang diproses"
            accent="emerald"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Sedang Dikirim"
            :value="$this->stats['sedang_dikirim']"
            caption="kurir di jalan"
            accent="amber"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 4m0 0v3a1 1 0 01-1 1h-2M14 8h4l3 4\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Diterima Bulan Ini"
            :value="$this->stats['diterima_bulan_ini']"
            caption="masuk etalase"
            accent="lime"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2.5\' d=\'M5 13l4 4L19 7\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Nilai Aktif"
            :value="'Rp ' . number_format($this->stats['nilai_aktif'], 0, ',', '.')"
            caption="modal titipan"
            accent="sky"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1\'/></svg>'"
        />
    </div>

    {{-- ===== Flash ===== --}}
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 backdrop-blur px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm scfs-fade-in-up">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 backdrop-blur px-4 py-3 text-sm font-bold text-rose-700 shadow-sm scfs-fade-in-up">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ===== Tabs ===== --}}
    <div class="mb-6 flex w-full overflow-x-auto rounded-2xl border border-white/60 bg-white/80 p-1.5 shadow-sm backdrop-blur-xl sm:w-max scrollbar-hide">
        <button wire:click="$set('statusFilter', 'aktif')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-emerald-500 to-lime-500 text-white shadow-md shadow-emerald-200' => $statusFilter === 'aktif',
            'text-gray-500 hover:bg-gray-50' => $statusFilter !== 'aktif',
        ])>Sedang Proses</button>
        <button wire:click="$set('statusFilter', 'selesai')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-emerald-600 to-emerald-500 text-white shadow-md shadow-emerald-200' => $statusFilter === 'selesai',
            'text-gray-500 hover:bg-gray-50' => $statusFilter !== 'selesai',
        ])>Telah Diterima</button>
        <button wire:click="$set('statusFilter', 'ditolak')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-rose-500 to-rose-600 text-white shadow-md shadow-rose-200' => $statusFilter === 'ditolak',
            'text-gray-500 hover:bg-gray-50' => $statusFilter !== 'ditolak',
        ])>Ditolak / Batal</button>
    </div>

    {{-- ===== Order list ===== --}}
    <div class="space-y-3">
        @forelse($this->supplyOrders as $i => $order)

            {{-- ============================================================ --}}
            {{-- VARIAN 1: COMPACT LIST ROW (untuk PO selesai / ditolak)        --}}
            {{-- ============================================================ --}}
            @if(in_array($order->status, ['selesai', 'ditolak']))
                @php
                    $isSelesai = $order->status === 'selesai';
                    $accent = $isSelesai
                        ? ['avatar' => 'from-emerald-500 to-lime-500', 'shadow' => 'shadow-emerald-200', 'pill' => 'bg-emerald-50 border-emerald-200 text-emerald-700', 'cta' => 'text-emerald-600', 'value' => 'text-emerald-600']
                        : ['avatar' => 'from-rose-500 to-rose-600',    'shadow' => 'shadow-rose-200',    'pill' => 'bg-rose-50 border-rose-200 text-rose-700',    'cta' => 'text-rose-600',    'value' => 'text-rose-600'];
                @endphp
                <button wire:key="order-{{ $order->id }}" wire:click="bukaModalDetail({{ $order->id }})" type="button"
                        class="scfs-fade-in-up group block w-full text-left relative overflow-hidden rounded-2xl border border-white/60 bg-white/85 backdrop-blur-xl px-4 sm:px-5 py-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:border-emerald-200"
                        style="animation-delay: {{ $i * 40 }}ms;">
                    <div class="flex items-center gap-4">
                        {{-- Status icon --}}
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $accent['avatar'] }} text-white shadow-md {{ $accent['shadow'] }} ring-2 ring-white">
                            @if($isSelesai)
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md border {{ $accent['pill'] }} px-2 py-0.5 text-[10px] font-black uppercase tracking-wider">{{ $order->nomor_order }}</span>
                                <span class="text-[10px] font-bold text-gray-400">{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y · H:i') }}</span>
                            </div>
                            <p class="mt-1 text-sm font-black leading-tight text-gray-900 truncate">{{ $order->pemasok->pemasokProfile->nama_perusahaan ?? $order->pemasok->name ?? 'Pemasok SCFS' }}</p>
                            <p class="text-[11px] font-medium text-gray-500">
                                {{ $order->details->count() }} item · <span class="font-black {{ $accent['value'] }}">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</span>
                            </p>
                        </div>

                        {{-- CTA --}}
                        <div class="hidden sm:flex items-center gap-1 text-[11px] font-black {{ $accent['cta'] }} group-hover:translate-x-0.5 transition">
                            Rincian
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                        <svg class="sm:hidden h-5 w-5 shrink-0 text-gray-300 group-hover:text-emerald-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>

            @else
            {{-- ============================================================ --}}
            {{-- VARIAN 2: FULL CARD (untuk PO aktif/dikirim)                    --}}
            {{-- ============================================================ --}}
            @php $tracking = $this->trackingFor($order); @endphp
            <article wire:key="order-{{ $order->id }}" class="scfs-fade-in-up relative overflow-hidden rounded-3xl border border-white/60 bg-white/85 backdrop-blur-xl p-5 sm:p-6 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)]" style="animation-delay: {{ $i * 60 }}ms;">

                {{-- Header --}}
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 mb-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700">{{ $order->nomor_order }}</span>
                        <span class="text-xs font-bold text-gray-400">Dipesan {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Nilai LKBB</p>
                        <p class="text-base font-black text-emerald-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>
                </header>

                <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                    {{-- Kiri: pengirim + timeline + barang --}}
                    <div class="space-y-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-lime-500 text-white shadow-md shadow-emerald-200 ring-2 ring-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16M5 9h14M5 13h14M5 17h14"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Dikirim oleh</p>
                                <h3 class="text-base font-black leading-tight text-gray-900 truncate">{{ $order->pemasok->pemasokProfile->nama_perusahaan ?? $order->pemasok->name ?? 'Pemasok SCFS' }}</h3>
                                <p class="mt-0.5 text-xs font-medium text-gray-500">Untuk tanggal kebutuhan {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</p>
                            </div>
                        </div>

                        {{-- Progress + timeline --}}
                        <div>
                            <x-tracking.progress-track :percentage="$tracking['progress']" variant="merchant" :label="'Progres Pengiriman'" :sublabel="$tracking['progress'] . '%'" />
                            <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                                <x-tracking.status-timeline :events="$tracking['events']" variant="merchant" />
                            </div>
                        </div>

                        {{-- Barang --}}
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Barang yang Diterima ({{ $order->details->count() }} item)</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach($order->details as $detail)
                                    <div class="flex items-center gap-2 rounded-xl border border-gray-100 bg-white/70 px-3 py-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-[11px] font-black text-emerald-700">{{ $detail->qty }}</div>
                                        <p class="truncate text-xs font-bold text-gray-800" title="{{ $detail->nama_produk_snapshot }}">{{ $detail->nama_produk_snapshot }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Kanan: courier card + aksi sesuai status --}}
                    <aside class="flex flex-col gap-3 border-t border-gray-100 pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        @if($order->nama_kurir)
                            <x-tracking.courier-card
                                :name="$order->nama_kurir"
                                :phone="$order->no_hp_kurir"
                                :resi="$order->no_resi"
                                :status="$order->status"
                                variant="merchant"
                            />
                        @endif

                        @if(in_array($order->status, ['menunggu_pemasok', 'menunggu_lkbb']))
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 text-center">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-600">⏳ Menunggu Persetujuan</p>
                                <p class="mt-1 text-[10px] font-medium text-gray-500">Pemasok & LKBB sedang meninjau.</p>
                            </div>
                        @elseif($order->status === 'diproses_pemasok')
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-center">
                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">📦 Disiapkan Pemasok</p>
                                <p class="mt-1 text-[10px] font-medium text-amber-700">Barang sedang dikemas.</p>
                            </div>
                        @elseif($order->status === 'dikirim')
                            <button wire:click="openConfirmModal({{ $order->id }})" class="w-full rounded-2xl bg-gradient-to-r from-emerald-500 to-lime-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 transition hover:shadow-xl hover:shadow-emerald-300 active:scale-[0.98] flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Barang Telah Diterima
                            </button>
                            @if($order->has_active_return)
                                <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-xs font-bold text-amber-700 transition hover:bg-amber-100">⏳ Ada Return Aktif — Lihat Status</a>
                            @else
                                <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-2.5 text-center text-xs font-bold text-rose-600 transition hover:bg-rose-50">Fisik Bermasalah? Ajukan Return</a>
                            @endif
                        @elseif($order->status === 'selesai')
                            <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-lime-50 border border-emerald-200 px-4 py-3 flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow shadow-emerald-300">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Selesai</p>
                                    <p class="text-[10px] font-bold text-emerald-600">Stok masuk etalase.</p>
                                </div>
                            </div>
                            @if($order->updated_at && $order->updated_at->diffInHours(now()) < 24)
                                @if($order->has_active_return)
                                    <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2 text-center text-[11px] font-bold text-amber-700 hover:bg-amber-100">⏳ Return Aktif — Lihat Status</a>
                                @else
                                    <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-2 text-center text-[11px] font-bold text-rose-600 hover:bg-rose-50">⚠ Masalah Setelah Cek? Ajukan Return</a>
                                @endif
                            @endif
                        @endif
                    </aside>
                </div>
            </article>
            @endif {{-- /varian compact vs full --}}
        @empty
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white/60 py-20 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 to-lime-100 text-emerald-500">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-800">Kategori Kosong</h3>
                <p class="mt-1 text-sm font-medium text-gray-500">Belum ada aktivitas logistik di tab ini.</p>
            </div>
        @endforelse

        {{-- Pagination --}}
        @if($this->supplyOrders->hasPages())
            <div class="mt-5">{{ $this->supplyOrders->links() }}</div>
        @endif
    </div>

    {{-- MODAL POP-UP KONFIRMASI (TETAP SAMA, DESAIN DIPERHALUS) --}}
    @if($showConfirmModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                
                {{-- Overlay Gelap --}}
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="closeConfirmModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Konten Modal --}}
                <div class="inline-block align-bottom bg-white rounded-[24px] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <div class="bg-white px-6 pt-8 pb-6 sm:p-8 sm:pb-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-emerald-50 border border-emerald-100 mb-6 shadow-inner">
                            <svg class="h-10 w-10 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        
                        <h3 class="text-2xl font-black text-gray-900 mb-2">Konfirmasi Barang Diterima?</h3>
                        <p class="text-sm text-gray-500 leading-relaxed font-medium">
                            Pastikan fisik barang sudah tiba di kantin Anda dan jumlahnya sesuai dengan surat jalan. Menekan "Ya" akan meresmikan modal titipan LKBB di etalase Anda.
                        </p>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3 border-t border-gray-100">
                        <button wire:click="konfirmasiTerima" type="button" class="w-full inline-flex justify-center items-center gap-2 rounded-xl px-6 py-3 bg-[#10b981] text-base font-black text-white hover:bg-[#059669] shadow-lg shadow-emerald-200 sm:w-auto sm:text-sm transition-all">
                            <span wire:loading.remove wire:target="konfirmasiTerima">Ya, Barang Sesuai</span>
                            <span wire:loading wire:target="konfirmasiTerima">Menyimpan...</span>
                        </button>
                        <button wire:click="closeConfirmModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-200 px-6 py-3 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm transition-all shadow-sm">
                            Cek Fisik Dulu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL LIHAT RINCIAN PESANAN (untuk PO yang sudah terkonfirmasi/ditolak) --}}
    <div x-data="{ open: @entangle('showModalDetail') }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" x-transition.opacity @click="$wire.tutupModalDetail()" class="fixed inset-0 bg-gray-900/50 backdrop-blur-md"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative z-50 flex w-full max-w-2xl max-h-[90vh] flex-col overflow-hidden rounded-[28px] border border-white/60 bg-white shadow-[0_30px_80px_-20px_rgba(16,185,129,0.45)]">

            @if($this->selectedOrder)
            @php
                $detailIsSelesai = $this->selectedOrder->status === 'selesai';
                $heroGrad = $detailIsSelesai
                    ? 'from-emerald-500 via-emerald-600 to-lime-500'
                    : 'from-rose-500 via-rose-600 to-rose-700';
                $heroShadowColor = $detailIsSelesai ? 'rgba(16,185,129,0.45)' : 'rgba(244,63,94,0.45)';
                $heroDecor = $detailIsSelesai ? 'bg-lime-300/30' : 'bg-rose-300/30';
            @endphp

            {{-- Hero header --}}
            <div class="relative overflow-hidden bg-gradient-to-br {{ $heroGrad }} px-6 py-5 shrink-0">
                <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl"></div>
                <div class="pointer-events-none absolute -left-8 -bottom-12 h-32 w-32 rounded-full {{ $heroDecor }} blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] {{ $detailIsSelesai ? 'text-emerald-100/90' : 'text-rose-100/90' }}">
                            {{ $detailIsSelesai ? 'Pesanan Selesai' : 'Pesanan Ditolak / Batal' }}
                        </p>
                        <h3 class="mt-1 truncate text-xl font-black tracking-tight text-white">{{ $this->selectedOrder->pemasok->pemasokProfile->nama_perusahaan ?? $this->selectedOrder->pemasok->name ?? 'Pemasok SCFS' }}</h3>
                        <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg border border-white/30 bg-white/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white backdrop-blur">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            {{ $this->selectedOrder->nomor_order }}
                        </span>
                    </div>
                    <button wire:click="tutupModalDetail" type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/30 bg-white/15 text-white shadow-md transition hover:bg-white/30 active:scale-95">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto bg-gradient-to-b {{ $detailIsSelesai ? 'from-emerald-50/40' : 'from-rose-50/40' }} to-white p-6 space-y-5">

                {{-- Tanggal & timestamp --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="relative overflow-hidden rounded-2xl border {{ $detailIsSelesai ? 'border-emerald-100' : 'border-rose-100' }} bg-white p-4 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest {{ $detailIsSelesai ? 'text-emerald-600' : 'text-rose-600' }}">Tanggal Pesan</p>
                        <p class="mt-1 text-sm font-black text-gray-900">{{ \Carbon\Carbon::parse($this->selectedOrder->created_at)->format('d M Y') }}</p>
                        <p class="text-[11px] font-bold text-gray-500">{{ \Carbon\Carbon::parse($this->selectedOrder->created_at)->format('H:i') }} WIB</p>
                    </div>
                    <div class="relative overflow-hidden rounded-2xl border {{ $detailIsSelesai ? 'border-lime-100' : 'border-rose-100' }} bg-white p-4 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest {{ $detailIsSelesai ? 'text-lime-600' : 'text-rose-600' }}">
                            {{ $detailIsSelesai ? 'Diterima' : 'Status Akhir' }}
                        </p>
                        <p class="mt-1 text-sm font-black text-gray-900">{{ \Carbon\Carbon::parse($this->selectedOrder->updated_at)->format('d M Y') }}</p>
                        <p class="text-[11px] font-bold text-gray-500">{{ \Carbon\Carbon::parse($this->selectedOrder->updated_at)->format('H:i') }} WIB</p>
                    </div>
                </div>

                {{-- Catatan merchant --}}
                @if($this->selectedOrder->catatan)
                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2.5 text-[11px] font-bold text-amber-800">
                        ✱ Catatan: {{ $this->selectedOrder->catatan }}
                    </div>
                @endif

                {{-- Kurir card (kalau ada) --}}
                @if($this->selectedOrder->nama_kurir)
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Diantar Oleh</p>
                        <x-tracking.courier-card
                            :name="$this->selectedOrder->nama_kurir"
                            :phone="$this->selectedOrder->no_hp_kurir"
                            :resi="$this->selectedOrder->no_resi"
                            :status="$this->selectedOrder->status"
                            variant="merchant"
                        />
                    </div>
                @endif

                {{-- Timeline --}}
                @if($detailIsSelesai)
                    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                        <x-tracking.progress-track :percentage="$this->selectedOrderProgress" variant="merchant" :label="'Progres Pengiriman'" :sublabel="$this->selectedOrderProgress . '%'" />
                        <div class="mt-4 rounded-xl bg-gray-50/60 p-3">
                            <x-tracking.status-timeline :events="$this->selectedOrderEvents" variant="merchant" />
                        </div>
                    </div>
                @endif

                {{-- Rincian barang --}}
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Rincian Barang ({{ $this->selectedOrder->details->count() }} item)</p>
                    <div class="overflow-hidden rounded-2xl border {{ $detailIsSelesai ? 'border-emerald-100' : 'border-rose-100' }} bg-white shadow-sm">
                        <table class="w-full text-left text-sm">
                            <thead class="text-[10px] uppercase {{ $detailIsSelesai ? 'bg-gradient-to-r from-emerald-50 to-lime-50' : 'bg-gradient-to-r from-rose-50 to-rose-100' }}">
                                <tr>
                                    <th class="px-4 py-3 font-black tracking-wider {{ $detailIsSelesai ? 'text-emerald-700' : 'text-rose-700' }}">Produk</th>
                                    <th class="px-4 py-3 text-center font-black tracking-wider {{ $detailIsSelesai ? 'text-emerald-700' : 'text-rose-700' }}">Qty</th>
                                    <th class="px-4 py-3 text-right font-black tracking-wider {{ $detailIsSelesai ? 'text-emerald-700' : 'text-rose-700' }}">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y {{ $detailIsSelesai ? 'divide-emerald-50' : 'divide-rose-50' }}">
                                @foreach($this->selectedOrder->details as $item)
                                <tr class="transition {{ $detailIsSelesai ? 'hover:bg-emerald-50/40' : 'hover:bg-rose-50/40' }}">
                                    <td class="px-4 py-3 font-bold text-gray-800">{{ $item->nama_produk_snapshot }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex h-7 min-w-[28px] items-center justify-center rounded-lg px-2 text-[11px] font-black {{ $detailIsSelesai ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $item->qty }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gradient-to-r {{ $detailIsSelesai ? 'from-emerald-500 to-lime-500' : 'from-rose-500 to-rose-600' }} text-white">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-right text-[11px] font-black uppercase tracking-widest">Total Nilai</td>
                                    <td class="px-4 py-3 text-right text-base font-black">Rp {{ number_format($this->selectedOrder->total_estimasi, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Return link (window 24 jam, hanya untuk selesai) --}}
                @if($detailIsSelesai && $this->selectedOrder->updated_at && $this->selectedOrder->updated_at->diffInHours(now()) < 24)
                    @if($this->selectedOrder->returns()->whereIn('status', ['pending_supplier_review', 'approved', 'escalated_lkbb'])->exists())
                        <a href="{{ route('merchant.form-return', $this->selectedOrder->id) }}" class="block w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-[11px] font-bold text-amber-700 hover:bg-amber-100">⏳ Return Aktif — Lihat Status</a>
                    @else
                        <a href="{{ route('merchant.form-return', $this->selectedOrder->id) }}" class="block w-full rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-center text-[11px] font-bold text-rose-600 hover:bg-rose-50">⚠ Ada masalah? Ajukan Return (dalam 24 jam)</a>
                    @endif
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 bg-white/80 px-6 py-4 backdrop-blur">
                <span class="text-[10px] font-bold text-gray-400">PO {{ $this->selectedOrder->nomor_order }}</span>
                <button wire:click="tutupModalDetail" type="button" class="rounded-xl bg-gradient-to-r from-gray-800 to-gray-900 px-5 py-2.5 text-sm font-black text-white shadow-md transition hover:from-gray-900 hover:to-black active:scale-[0.98]">Tutup</button>
            </div>
            @endif
        </div>
    </div>
</div>