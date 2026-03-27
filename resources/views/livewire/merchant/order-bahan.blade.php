<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\BahanBaku;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderDetail;
use App\Models\MerchantProfile;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $search = '';
    public array $cart = []; 
    public $tanggal_kebutuhan = '';
    public $catatan = '';

    public function mount()
    {
        // Set default pengiriman adalah H+1 (Besok)
        $this->tanggal_kebutuhan = Carbon::tomorrow()->format('Y-m-d');
        
        // Proteksi
        $profile = MerchantProfile::where('user_id', Auth::id())->first();
        if (!$profile || $profile->status_verifikasi !== 'disetujui') {
            abort(403, 'Kantin Anda belum diverifikasi LKBB.');
        }
    }

    #[Computed]
    public function katalogBahan()
    {
        // Simulasi jika belum ada data, Anda bisa insert manual dulu di DB untuk testing
        return BahanBaku::where('is_tersedia', true)
                ->when($this->search, fn($q) => $q->where('nama_bahan', 'like', '%'.$this->search.'%'))
                ->get();
    }

    // --- KERANJANG BELANJA BAHAN ---
    public function addToCart($id)
    {
        $bahan = BahanBaku::find($id);
        if (!$bahan) return;

        if (isset($this->cart[$id])) {
            $this->cart[$id]['qty']++;
        } else {
            $this->cart[$id] = [
                'id' => $bahan->id,
                'nama' => $bahan->nama_bahan,
                'harga' => (float)$bahan->harga_estimasi,
                'satuan' => $bahan->satuan,
                'qty' => 1
            ];
        }
    }

    public function decreaseQty($id)
    {
        if (isset($this->cart[$id])) {
            if ($this->cart[$id]['qty'] > 1) {
                $this->cart[$id]['qty']--;
            } else {
                unset($this->cart[$id]);
            }
        }
    }

    public function removeFromCart($id)
    {
        unset($this->cart[$id]);
    }

    #[Computed]
    public function cartTotal()
    {
        return array_reduce($this->cart, function ($carry, $item) {
            return $carry + ($item['harga'] * $item['qty']);
        }, 0);
    }

    // --- EKSEKUSI PENGIRIMAN ORDER ---
    public function submitOrder()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Keranjang order masih kosong.');
            return;
        }

        $this->validate([
            'tanggal_kebutuhan' => 'required|date|after_or_equal:tomorrow',
            'catatan' => 'nullable|string|max:500'
        ], [
            'tanggal_kebutuhan.after_or_equal' => 'Tanggal pengiriman minimal adalah besok hari.'
        ]);

        try {
            DB::transaction(function () {
                // 1. Buat Header Order (Draft Tagihan ke LKBB)
                $order = SupplyOrder::create([
                    'nomor_order' => 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                    'merchant_id' => Auth::id(),
                    'total_estimasi' => 0, // Akan diupdate nanti
                    'tanggal_kebutuhan' => $this->tanggal_kebutuhan,
                    'catatan' => $this->catatan,
                    'status' => 'menunggu_lkbb'
                ]);

                $realTotal = 0;

                // 2. Insert Snapshot Detail & Kalkulasi Total Asli
                foreach ($this->cart as $item) {
                    $bahan = BahanBaku::findOrFail($item['id']);
                    $subtotal = $bahan->harga_estimasi * $item['qty'];
                    
                    SupplyOrderDetail::create([
                        'supply_order_id' => $order->id,
                        'bahan_baku_id' => $bahan->id,
                        'nama_bahan_snapshot' => $bahan->nama_bahan,
                        'harga_satuan_snapshot' => $bahan->harga_estimasi,
                        'satuan_snapshot' => $bahan->satuan,
                        'qty' => $item['qty'],
                        'subtotal' => $subtotal
                    ]);

                    $realTotal += $subtotal;
                }

                // 3. Update Total Estimasi Asli
                $order->update(['total_estimasi' => $realTotal]);
            });

            // Reset UI
            $this->cart = [];
            $this->reset(['catatan']);
            $this->tanggal_kebutuhan = Carbon::tomorrow()->format('Y-m-d');
            
            session()->flash('success', 'Pre-Order berhasil dikirim! Menunggu persetujuan pendanaan dari LKBB.');

        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}; ?>

<div class="h-[calc(100vh-5rem)] w-full flex flex-col md:flex-row bg-gray-100/50">
    
    {{-- BAGIAN KIRI: KATALOG BAHAN BAKU (2/3 Layar) --}}
    <div class="w-full md:w-2/3 h-full flex flex-col p-4 md:p-6 overflow-hidden">
        
        <div class="mb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900">Order Bahan Baku (Pre-Order)</h2>
                <p class="text-xs font-medium text-gray-500 mt-1">Pilih kebutuhan stok besok. Tagihan akan ditalangi oleh LKBB.</p>
            </div>
            
            <div class="relative w-full md:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari bahan (Beras, Minyak...)" 
                    class="w-full py-2.5 pl-9 pr-4 text-sm bg-white border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition shadow-sm">
            </div>
        </div>

        {{-- Product Grid (Scrollable) --}}
        <div class="flex-1 overflow-y-auto scrollbar-hide pr-2">
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-10">
                @forelse($this->katalogBahan as $item)
                    <div wire:click="addToCart({{ $item->id }})" class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-blue-300 transition-all cursor-pointer group overflow-hidden flex flex-col relative">
                        <div class="absolute top-2 right-2 z-10 bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded-md text-[10px] font-bold text-gray-600 shadow-sm">
                            / {{ $item->satuan }}
                        </div>
                        <div class="h-28 w-full bg-gray-50 relative overflow-hidden flex items-center justify-center">
                            @if($item->foto_bahan)
                                <img src="{{ asset('storage/' . $item->foto_bahan) }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <div class="text-4xl opacity-30">📦</div>
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="text-xs font-bold text-gray-900 leading-tight line-clamp-2 mb-1 group-hover:text-blue-700 transition-colors">{{ $item->nama_bahan }}</h3>
                            <p class="text-sm font-extrabold text-blue-600">Rp{{ number_format($item->harga_estimasi, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center border-2 border-dashed border-gray-200 rounded-2xl bg-white">
                        <p class="text-gray-400 text-sm font-bold">Belum ada katalog bahan baku dari Pusat.</p>
                        <p class="text-xs text-gray-400 mt-1">Silakan hubungi Admin LKBB untuk menambahkan master data bahan.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- BAGIAN KANAN: DRAFT ORDER & CHECKOUT (1/3 Layar) --}}
    <div class="w-full md:w-1/3 h-full bg-white border-l border-gray-200 shadow-xl flex flex-col relative z-10">
        
        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-extrabold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Draft Permintaan Stok
                </h3>
                <button wire:click="clearCart" class="text-[10px] font-bold text-rose-500 hover:text-rose-700 uppercase tracking-wider transition-colors">Kosongkan</button>
            </div>

            {{-- List Order --}}
            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50/30">
                @if(empty($cart))
                    <div class="h-full flex flex-col items-center justify-center text-gray-400 opacity-70">
                        <svg class="w-16 h-16 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        <p class="text-sm font-bold">Belum Ada Request</p>
                    </div>
                @else
                    @foreach($cart as $id => $item)
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                            <div class="flex-1 pr-3 min-w-0">
                                <h4 class="text-xs font-bold text-gray-900 truncate">{{ $item['nama'] }}</h4>
                                <p class="text-[10px] font-bold text-blue-600 mt-0.5">Rp{{ number_format($item['harga'], 0, ',', '.') }} <span class="text-gray-400 font-normal">/ {{ $item['satuan'] }}</span></p>
                            </div>
                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-1 border border-gray-200 flex-shrink-0">
                                <button wire:click="decreaseQty({{ $id }})" class="w-6 h-6 flex items-center justify-center bg-white text-gray-600 rounded shadow-sm hover:bg-gray-100 font-bold focus:outline-none">-</button>
                                <span class="text-xs font-extrabold w-5 text-center">{{ $item['qty'] }}</span>
                                <button wire:click="addToCart({{ $id }})" class="w-6 h-6 flex items-center justify-center bg-blue-100 text-blue-700 rounded shadow-sm hover:bg-blue-200 font-bold focus:outline-none">+</button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Checkout Form --}}
            <div class="p-5 bg-white border-t border-gray-200 shadow-[0_-10px_20px_rgba(0,0,0,0.03)]">
                
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tgl. Kebutuhan / Pengiriman</label>
                        <input wire:model="tanggal_kebutuhan" type="date" min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                            class="w-full py-2.5 px-3 text-sm font-bold text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition">
                        @error('tanggal_kebutuhan') <span class="text-[10px] font-bold text-rose-500 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Catatan Khusus (Opsional)</label>
                        <textarea wire:model="catatan" rows="2" placeholder="Cth: Tolong ayamnya dipotong 8 bagian..." 
                            class="w-full py-2.5 px-3 text-xs text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition"></textarea>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-3 mb-4 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Estimasi Dana LKBB</span>
                        <span class="text-[9px] text-gray-400 italic">Merchant tidak bayar tunai</span>
                    </div>
                    <span class="text-xl font-extrabold text-blue-600">Rp{{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                </div>

                <button wire:click="submitOrder" wire:loading.attr="disabled"
                    @if(empty($cart)) disabled @endif
                    class="w-full py-3.5 text-sm font-extrabold text-white rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 focus:ring-4 bg-gray-900 hover:bg-gray-800 shadow-gray-300 focus:ring-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    
                    <span wire:loading.remove wire:target="submitOrder">AJUKAN DANA KE LKBB</span>
                    <span wire:loading wire:target="submitOrder">MENGIRIM...</span>
                </button>
                
                @if(session('error'))
                    <div class="mt-3 text-[10px] font-bold text-rose-600 bg-rose-50 px-3 py-2 rounded-lg text-center border border-rose-200 animate-pulse">
                        {{ session('error') }}
                    </div>
                @endif
                @if(session('success'))
                    <div class="mt-3 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg text-center border border-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>