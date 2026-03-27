<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SupplyOrder;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    // UI State
    public $statusFilter = 'aktif'; // 'aktif' (belum selesai), 'selesai'
    public $search = '';

    /**
     * ENGINE UTAMA: Mengambil daftar pesanan beserta detail barangnya (Eager Loading)
     */
    #[Computed]
    public function supplyOrders()
    {
        $query = SupplyOrder::with('details') // Eager loading mencegah N+1 Problem
            ->where('merchant_id', Auth::id());

        // Pencarian berdasarkan Nomor Order
        if ($this->search) {
            $query->where('nomor_order', 'like', '%' . trim($this->search) . '%');
        }

        // Filter Tab Aktif vs Selesai
        if ($this->statusFilter === 'aktif') {
            $query->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim']);
        } elseif ($this->statusFilter === 'selesai') {
            $query->where('status', 'selesai');
        } elseif ($this->statusFilter === 'ditolak') {
            $query->where('status', 'ditolak');
        }

        // Tampilkan yang paling baru berubah statusnya atau dibuat
        return $query->orderBy('updated_at', 'desc')->get();
    }

    /**
     * CORE ACTION: Konfirmasi Penerimaan Barang Fisik
     */
    public function konfirmasiTerima($orderId)
    {
        try {
            DB::transaction(function () use ($orderId) {
                // 1. Strict Query & Pessimistic Lock (Anti-IDOR & Anti-Race Condition)
                $order = SupplyOrder::where('id', $orderId)
                            ->where('merchant_id', Auth::id())
                            ->lockForUpdate()
                            ->firstOrFail();

                // 2. State Machine Validation (Validasi Siklus Status)
                if ($order->status !== 'dikirim') {
                    throw new \Exception('Aksi ditolak. Pesanan ini belum berstatus "Dikirim" oleh Pemasok.');
                }

                // 3. Eksekusi Perubahan Status
                $order->update([
                    'status' => 'selesai'
                ]);
                
                // Note: Jika di masa depan aplikasi membutuhkan fitur penambahan 
                // kuantitas stok otomatis ke Gudang Merchant, kodenya ditaruh di sini.
            });

            session()->flash('success', 'Penerimaan barang berhasil dikonfirmasi! Siklus order telah selesai.');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full max-w-6xl mx-auto space-y-6 relative">
    
    {{-- Header & Info --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Penerimaan Barang Supply</h2>
            <p class="text-gray-500 text-sm mt-1">Lacak status Pre-Order bahan baku Anda dan konfirmasi saat fisik barang tiba.</p>
        </div>
    </div>

    {{-- Global Flash Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6 animate-pulse">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tab Filter & Search --}}
    <div class="bg-white p-3 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-3">
        <div class="flex bg-gray-100 rounded-xl p-1 overflow-x-auto scrollbar-hide">
            <button wire:click="$set('statusFilter', 'aktif')" class="flex-1 min-w-[120px] px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $statusFilter == 'aktif' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Sedang Proses</button>
            <button wire:click="$set('statusFilter', 'selesai')" class="flex-1 min-w-[120px] px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $statusFilter == 'selesai' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Selesai Diterima</button>
            <button wire:click="$set('statusFilter', 'ditolak')" class="flex-1 min-w-[120px] px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $statusFilter == 'ditolak' ? 'bg-white text-rose-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Ditolak LKBB</button>
        </div>
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari No. Order..." 
                class="w-full py-2.5 pl-9 pr-4 text-sm text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition">
        </div>
    </div>

    {{-- List Pesanan --}}
    <div class="space-y-4">
        @forelse($this->supplyOrders as $order)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col md:flex-row transition hover:shadow-md" wire:key="order-{{ $order->id }}">
                
                {{-- Info Kiri (Summary) --}}
                <div class="p-5 md:w-1/3 border-b md:border-b-0 md:border-r border-gray-100 bg-gray-50/30 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-bold text-gray-900 font-mono">{{ $order->nomor_order }}</span>
                            
                            {{-- Visual Status Badge --}}
                            @if($order->status == 'menunggu_lkbb')
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-[9px] font-extrabold uppercase tracking-wider">⏳ Menunggu Dana</span>
                            @elseif($order->status == 'diproses_pemasok')
                                <span class="px-2 py-1 bg-amber-50 text-amber-600 border border-amber-200 rounded text-[9px] font-extrabold uppercase tracking-wider flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Disiapkan</span>
                            @elseif($order->status == 'dikirim')
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[9px] font-extrabold uppercase tracking-wider">🚚 Dlm Pengiriman</span>
                            @elseif($order->status == 'selesai')
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[9px] font-extrabold uppercase tracking-wider">✅ Selesai</span>
                            @elseif($order->status == 'ditolak')
                                <span class="px-2 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded text-[9px] font-extrabold uppercase tracking-wider">❌ Ditolak</span>
                            @endif
                        </div>
                        <p class="text-[10px] text-gray-500 font-medium">Req. Kirim: <span class="text-gray-800 font-bold">{{ Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</span></p>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Total Ditalangi LKBB</p>
                        <p class="text-lg font-extrabold text-blue-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Info Kanan (Rincian Barang & Action) --}}
                <div class="p-5 md:w-2/3 flex flex-col justify-between">
                    <div>
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Rincian Barang yang Dikirim:</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            @foreach($order->details as $detail)
                                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 border border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded bg-white flex items-center justify-center text-gray-500 text-xs font-bold border border-gray-200 shadow-sm">
                                            {{ $detail->qty }}
                                        </div>
                                        <div class="max-w-[120px]">
                                            <p class="text-xs font-bold text-gray-900 truncate" title="{{ $detail->nama_bahan_snapshot }}">{{ $detail->nama_bahan_snapshot }}</p>
                                            <p class="text-[9px] text-gray-500">{{ $detail->satuan_snapshot }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-bold text-gray-600">Rp{{ number_format($detail->subtotal, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($order->catatan)
                            <div class="p-3 rounded-lg bg-yellow-50/50 border border-yellow-100 text-xs text-yellow-800 mb-4">
                                <span class="font-bold">Catatan Anda:</span> {{ $order->catatan }}
                            </div>
                        @endif
                    </div>

                    {{-- Tombol Aksi Kritis --}}
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        @if($order->status == 'dikirim')
                            <button wire:click="konfirmasiTerima({{ $order->id }})" 
                                wire:confirm="Pastikan fisik barang sudah tiba dan sesuai rincian. Lanjutkan konfirmasi terima?"
                                class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-extrabold rounded-xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition flex items-center gap-2 focus:ring-4 focus:ring-emerald-100">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Konfirmasi Barang Fisik Diterima
                            </button>
                        @elseif($order->status == 'selesai')
                            <span class="text-xs font-bold text-emerald-600 flex items-center gap-1 bg-emerald-50 px-3 py-1.5 rounded-lg">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Transaksi Tuntas (Tercatat)
                            </span>
                        @else
                            <span class="text-xs font-medium text-gray-400 italic">
                                Belum bisa konfirmasi (Menunggu diproses/dikirim)
                            </span>
                        @endif
                    </div>
                </div>

            </div>
        @empty
            <div class="py-20 text-center border-2 border-dashed border-gray-200 rounded-3xl bg-gray-50/50">
                <div class="text-5xl mb-4 opacity-30 flex justify-center">🚚</div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Tidak ada pesanan {{ $statusFilter == 'aktif' ? 'aktif' : 'selesai' }}</h3>
                <p class="text-gray-500 text-sm">Pesanan bahan baku dari LKBB akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

</div>