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
}; ?>

<div class="py-8 px-6 md:px-8 w-full max-w-7xl mx-auto space-y-6 relative">
    
    {{-- Header & Info --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b border-gray-200 pb-5">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Penerimaan Logistik</h2>
            <p class="text-gray-500 text-sm mt-1">Pantau status pesanan dan konfirmasi saat armada Pemasok tiba di kantin Anda.</p>
        </div>
        
        <div class="w-full sm:w-auto relative">
            <input type="text" wire:model.live="search" placeholder="Cari No. Order..." class="w-full sm:w-72 pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 shadow-sm font-bold text-gray-700 transition">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    {{-- Global Flash Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6 font-bold">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6 font-bold">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tab Filter --}}
    <div class="flex overflow-x-auto space-x-2 bg-white p-1.5 rounded-2xl w-full sm:w-max mb-6 border border-gray-100 shadow-sm scrollbar-hide">
        <button wire:click="$set('statusFilter', 'aktif')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all relative {{ $statusFilter === 'aktif' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50' }}">
            📦 Sedang Proses 
        </button>
        <button wire:click="$set('statusFilter', 'selesai')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $statusFilter === 'selesai' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-500 hover:bg-gray-50' }}">
            ✅ Telah Diterima
        </button>
        <button wire:click="$set('statusFilter', 'ditolak')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $statusFilter === 'ditolak' ? 'bg-rose-50 text-rose-600' : 'text-gray-500 hover:bg-gray-50' }}">
            ❌ Ditolak / Batal
        </button>
    </div>

    {{-- List Pesanan --}}
    <div class="space-y-4">
        @forelse($this->supplyOrders as $order)
            <div class="bg-white rounded-[20px] shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row gap-6 hover:shadow-md transition" wire:key="order-{{ $order->id }}">
                
                {{-- Kiri: Detail PO & Pengirim --}}
                <div class="flex-1 space-y-4">
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                        <span class="px-3 py-1 bg-gray-50 border border-gray-200 text-gray-700 text-[10px] font-black tracking-wider rounded-lg">{{ $order->nomor_order }}</span>
                        <span class="text-xs font-bold text-gray-400">Order: {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Pengirim (Pemasok)</p>
                            <h3 class="text-sm font-black text-gray-900">{{ $order->pemasok->pemasokProfile->nama_perusahaan ?? $order->pemasok->name ?? 'Pemasok SCFS' }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Dikirim Untuk: Tgl {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Info Kurir</p>
                            @if($order->nama_kurir)
                                <div class="text-xs font-bold text-blue-600 bg-blue-50 p-2 rounded-lg border border-blue-100 space-y-0.5">
                                    <p>👤 {{ $order->nama_kurir }}</p>
                                    <p>📞 <a href="tel:{{ $order->no_hp_kurir }}" class="underline">{{ $order->no_hp_kurir }}</a></p>
                                    <p class="text-[10px] text-blue-500">Resi: {{ $order->no_resi }}</p>
                                </div>
                            @else
                                <p class="text-xs font-bold text-gray-400 italic">Belum ada info kurir</p>
                            @endif
                        </div>
                    </div>

                    <div class="pt-3">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Barang yang Akan Diterima:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($order->details as $detail)
                                <div class="flex items-center gap-2 bg-gray-50 border border-gray-100 px-3 py-2 rounded-xl">
                                    <div class="w-7 h-7 rounded bg-white flex items-center justify-center text-gray-700 text-xs font-black shadow-sm">
                                        {{ $detail->qty }}
                                    </div>
                                    <p class="text-xs font-bold text-gray-800 truncate" title="{{ $detail->nama_produk_snapshot }}">{{ $detail->nama_produk_snapshot }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Kanan: Aksi & Nilai Total --}}
                <div class="flex flex-col justify-end lg:items-end gap-3 lg:w-72 border-t lg:border-t-0 lg:border-l border-gray-100 pt-4 lg:pt-0 lg:pl-6 shrink-0">
                    <div class="w-full text-left lg:text-right mb-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Nilai Barang LKBB</p>
                        <p class="text-2xl font-black text-gray-900">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>

                    {{-- Logic Status Panel --}}
                    @if(in_array($order->status, ['menunggu_pemasok', 'menunggu_lkbb']))
                        <div class="w-full bg-gray-50 p-3 rounded-xl border border-gray-200 text-center">
                            <span class="text-[10px] font-extrabold text-gray-600 uppercase tracking-wider">⏳ Diproses Sistem</span>
                            <p class="text-[10px] text-gray-500 font-medium mt-1">Menunggu persetujuan Pemasok & LKBB.</p>
                        </div>

                    @elseif($order->status === 'diproses_pemasok')
                        <div class="w-full bg-amber-50 p-3 rounded-xl border border-amber-200 text-center">
                            <span class="text-[10px] font-extrabold text-amber-600 uppercase tracking-wider">📦 Sedang Disiapkan</span>
                            <p class="text-[10px] text-amber-700 font-medium mt-1">Pemasok sedang mengepak barang Anda.</p>
                        </div>

                   @elseif($order->status === 'dikirim')
                        {{-- TOMBOL TERIMA BARANG & RETURN (SAAT PROSES CEK FISIK) --}}
                        <div class="w-full flex flex-col gap-2">
                            <div class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 p-3 rounded-2xl shadow-lg shadow-blue-200/50 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0 shadow-inner">
                                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-lg font-black text-white tracking-wide leading-none mb-1">SEDANG JALAN</h4>
                                    <p class="text-[10px] font-medium text-blue-100 leading-tight">Kurir Pemasok menuju kantin.</p>
                                </div>
                            </div>
                            
                            <button wire:click="openConfirmModal({{ $order->id }})" class="w-full bg-[#10b981] border border-[#059669] text-white font-black py-3 rounded-2xl hover:bg-[#059669] transition-all text-sm shadow-lg shadow-emerald-200 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                BARANG TELAH DITERIMA
                            </button>

                            @if($order->has_active_return)
                                <a href="{{ route('merchant.form-return', $order->id) }}"
                                   class="w-full bg-amber-50 border border-amber-200 text-amber-700 font-bold py-2.5 rounded-2xl hover:bg-amber-100 transition-all text-xs flex items-center justify-center gap-2 shadow-sm">
                                    ⏳ Sudah Ada Return Aktif — Lihat Status
                                </a>
                            @else
                                <a href="{{ route('merchant.form-return', $order->id) }}"
                                   class="w-full bg-white border border-rose-200 text-rose-600 font-bold py-2.5 rounded-2xl hover:bg-rose-50 hover:border-rose-300 transition-all text-xs flex items-center justify-center gap-2 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Fisik Bermasalah? Ajukan Return
                                </a>
                            @endif
                        </div>

                    @elseif($order->status === 'selesai')
                        <div class="w-full flex flex-col gap-2">
                            <div class="w-full bg-emerald-50 p-4 rounded-xl border border-emerald-200 flex items-center gap-3">
                                <div class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Selesai</p>
                                    <p class="text-[10px] text-emerald-600 font-bold">Stok telah masuk etalase.</p>
                                </div>
                            </div>

                            {{-- Window return 24 jam masih bisa diajukan setelah terima --}}
                            @if($order->updated_at && $order->updated_at->diffInHours(now()) < 24)
                                @if($order->has_active_return)
                                    <a href="{{ route('merchant.form-return', $order->id) }}"
                                       class="w-full bg-amber-50 border border-amber-200 text-amber-700 font-bold py-2 rounded-xl hover:bg-amber-100 text-[11px] text-center">
                                        ⏳ Return Aktif — Lihat Status
                                    </a>
                                @else
                                    <a href="{{ route('merchant.form-return', $order->id) }}"
                                       class="w-full bg-white border border-rose-200 text-rose-600 font-bold py-2 rounded-xl hover:bg-rose-50 text-[11px] text-center">
                                        ⚠ Masalah Setelah Cek Lebih Detail? Ajukan Return
                                    </a>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>

            </div>
        @empty
            <div class="py-20 text-center border border-gray-100 rounded-[24px] bg-white shadow-sm flex flex-col items-center justify-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                    <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                </div>
                <h3 class="text-lg font-black text-gray-800 mb-1">Kategori Kosong</h3>
                <p class="text-gray-500 text-sm font-medium">Belum ada aktivitas logistik di tab ini.</p>
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