<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SupplyOrder;

new #[Layout('layouts.app')] 
class extends Component {
    
    // UI State
    public $statusFilter = 'aktif'; // 'aktif' (belum selesai), 'selesai', 'ditolak'
    public $search = '';

    // PROPERTI BARU UNTUK MODAL
    public $showConfirmModal = false;
    public $selectedOrderId = null;

    // FUNGSI UNTUK MEMBUKA MODAL
    public function openConfirmModal($id)
    {
        $this->selectedOrderId = $id;
        $this->showConfirmModal = true;
    }

    // FUNGSI UNTUK MENUTUP MODAL
    public function closeConfirmModal()
    {
        $this->showConfirmModal = false;
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

        return $query->orderBy('updated_at', 'desc')->get();
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
    <div class="space-y-4">
        @forelse($this->supplyOrders as $i => $order)
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
        @empty
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white/60 py-20 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 to-lime-100 text-emerald-500">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-800">Kategori Kosong</h3>
                <p class="mt-1 text-sm font-medium text-gray-500">Belum ada aktivitas logistik di tab ini.</p>
            </div>
        @endforelse
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
</div>