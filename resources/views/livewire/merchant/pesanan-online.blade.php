<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\OnlineOrder;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    // State Filter Tab Aktif: 'baru', 'proses', 'siap'
    public string $activeTab = 'baru';

    #[Computed]
    public function menungguKonfirmasi()
    {
        return OnlineOrder::with(['items', 'mahasiswa'])
            ->where('merchant_id', Auth::id())
            ->where('status', 'menunggu_konfirmasi')
            ->oldest() 
            ->get();
    }

    #[Computed]
    public function sedangDiproses()
    {
        return OnlineOrder::with(['items', 'mahasiswa'])
            ->where('merchant_id', Auth::id())
            ->where('status', 'diproses')
            ->oldest()
            ->get();
    }

    #[Computed]
    public function siapDiambil()
    {
        return OnlineOrder::with(['items', 'mahasiswa'])
            ->where('merchant_id', Auth::id())
            ->where('status', 'siap_diambil')
            ->oldest()
            ->get();
    }

    public function terimaPesanan($id)
    {
        $order = OnlineOrder::where('merchant_id', Auth::id())->findOrFail($id);
        $order->update(['status' => 'diproses']);
        $this->activeTab = 'proses'; // Auto pindah tab biar ketahuan sedang dimasak
    }

    public function makananSiap($id)
    {
        $order = OnlineOrder::where('merchant_id', Auth::id())->findOrFail($id);
        $order->update(['status' => 'siap_diambil']);
        $this->activeTab = 'siap'; // Auto pindah tab ke siap ambil
    }

    public function serahkanMakanan($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $order = OnlineOrder::with('items')->where('merchant_id', Auth::id())->lockForUpdate()->findOrFail($id);
                if($order->status !== 'siap_diambil') throw new \Exception("Pesanan belum siap.");
                $order->update(['status' => 'selesai']);
            });
            session()->flash('success', 'Pesanan berhasil diserahkan ke mahasiswa!');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function tolakPesanan($id)
    {
        $order = OnlineOrder::where('merchant_id', Auth::id())->findOrFail($id);
        $order->update(['status' => 'dibatalkan']);
        session()->flash('error', 'Pesanan berhasil ditolak & dibatalkan.');
    }
}; ?>

<div class="py-6 px-6 md:px-8 w-full space-y-6 bg-slate-50 min-h-screen flex flex-col">
    
    {{-- Header Area --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-5 shrink-0">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                🍳 Dapur Pesanan Online
                <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[9px] font-black bg-emerald-100 text-[#059669] tracking-widest border border-emerald-200 uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#059669] animate-pulse"></span> Live
                </span>
            </h2>
            <p class="text-gray-500 text-xs mt-1 font-medium">Sistem Manajemen Pesanan Masuk ShopeeFood Style.</p>
        </div>
    </div>

    {{-- Alert Flash --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold px-4 py-3 rounded-xl shadow-sm shrink-0">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold px-4 py-3 rounded-xl shadow-sm shrink-0">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- NAVIGATION TABS (ShopeeFood Partner Style) --}}
    <div class="flex border-b border-gray-200 bg-white rounded-xl p-1.5 shadow-sm shrink-0" wire:poll.10s>
        <button wire:click="$set('activeTab', 'baru')" 
            class="flex-1 py-3 text-xs font-black rounded-lg transition-all flex items-center justify-center gap-2
            {{ $activeTab === 'baru' ? 'bg-[#059669] text-white shadow-md' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
            📥 Pesanan Baru
            <span class="px-2 py-0.5 text-[10px] rounded-md {{ $activeTab === 'baru' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-600' }} font-black">
                {{ $this->menungguKonfirmasi->count() }}
            </span>
        </button>
        
        <button wire:click="$set('activeTab', 'proses')" 
            class="flex-1 py-3 text-xs font-black rounded-lg transition-all flex items-center justify-center gap-2
            {{ $activeTab === 'proses' ? 'bg-[#059669] text-white shadow-md' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
            🍳 Sedang Dimasak
            <span class="px-2 py-0.5 text-[10px] rounded-md {{ $activeTab === 'proses' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }} font-black">
                {{ $this->sedangDiproses->count() }}
            </span>
        </button>
        
        <button wire:click="$set('activeTab', 'siap')" 
            class="flex-1 py-3 text-xs font-black rounded-lg transition-all flex items-center justify-center gap-2
            {{ $activeTab === 'siap' ? 'bg-[#059669] text-white shadow-md' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
            🛍️ Siap Diambil
            <span class="px-2 py-0.5 text-[10px] rounded-md {{ $activeTab === 'siap' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700' }} font-black">
                {{ $this->siapDiambil->count() }}
            </span>
        </button>
    </div>

    {{-- MAIN ORDER CONTENT AREA --}}
    <div class="flex-1 overflow-y-auto min-h-0 pr-1">
        
        {{-- ================= TAB 1: PESANAN BARU ================= --}}
        @if($activeTab === 'baru')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($this->menungguKonfirmasi as $order)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                        <div>
                            {{-- Card Header --}}
                            <div class="p-4 bg-slate-50/50 border-b border-gray-100 flex justify-between items-center">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 font-mono tracking-wider">{{ $order->order_id }}</span>
                                    <h4 class="text-sm font-black text-gray-900 mt-0.5">{{ $order->mahasiswa->name ?? 'Mahasiswa' }}</h4>
                                </div>
                                <span class="text-[10px] font-black text-rose-600 bg-rose-50 px-2 py-1 rounded-md border border-rose-100">BELUM ACC</span>
                            </div>
                            
                            {{-- Items List --}}
                            <div class="p-4 space-y-3">
                                <div class="space-y-2 border-b border-dashed border-gray-200 pb-3">
                                    @foreach($order->items as $item)
                                        <div class="flex justify-between items-start text-sm">
                                            <div class="font-bold text-gray-800 min-w-0 pr-4">
                                                <span class="text-[#059669] font-black mr-2">{{ $item->qty }}x</span>{{ $item->nama_produk_snapshot }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                @if($order->catatan_pembeli)
                                    <div class="p-2.5 bg-amber-50/70 border border-amber-100 rounded-xl text-xs font-bold text-amber-800 flex gap-1.5 items-start">
                                        <span class="shrink-0">💡</span>
                                        <span>Catatan: "{{ $order->catatan_pembeli }}"</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Card Footer Actions --}}
                        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center gap-3">
                            <div class="text-left">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Total Dana</p>
                                <p class="text-sm font-black text-gray-900">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <button wire:click="tolakPesanan({{ $order->id }})" wire:confirm="Tolak pesanan ini?" class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-rose-50 text-gray-500 hover:text-rose-600 text-xs font-black rounded-xl transition-colors">
                                    Tolak
                                </button>
                                <button wire:click="terimaPesanan({{ $order->id }})" class="px-5 py-2.5 bg-[#059669] hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-sm transition-colors">
                                    Terima & Masak
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-24 text-center bg-white border border-gray-200 rounded-2xl shadow-sm">
                        <div class="text-4xl mb-3">📥</div>
                        <h4 class="font-black text-gray-900 text-sm">Belum Ada Pesanan Masuk</h4>
                        <p class="text-xs text-gray-400 mt-1">Layar akan otomatis terupdate jika ada mahasiswa yang checkout online.</p>
                    </div>
                @endforelse
            </div>

        {{-- ================= TAB 2: SEDANG DIPROSES ================= --}}
        @elseif($activeTab === 'proses')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($this->sedangDiproses as $order)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow relative">
                        <div class="absolute top-0 left-0 w-1 h-full bg-amber-500"></div>
                        <div>
                            <div class="p-4 bg-slate-50/50 border-b border-gray-100 flex justify-between items-center pl-5">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 font-mono tracking-wider">{{ $order->order_id }}</span>
                                    <h4 class="text-sm font-black text-gray-900 mt-0.5">{{ $order->mahasiswa->name ?? 'Mahasiswa' }}</h4>
                                </div>
                                <span class="text-[10px] font-black text-amber-700 bg-amber-50 px-2 py-1 rounded-md border border-amber-100">DIMASAK</span>
                            </div>
                            
                            <div class="p-4 space-y-3 pl-5">
                                <div class="space-y-2 border-b border-dashed border-gray-200 pb-3">
                                    @foreach($order->items as $item)
                                        <div class="text-sm font-bold text-gray-800">
                                            <span class="text-amber-500 font-black mr-2">{{ $item->qty }}x</span>{{ $item->nama_produk_snapshot }}
                                        </div>
                                    @endforeach
                                </div>
                                @if($order->catatan_pembeli)
                                    <p class="text-xs font-bold text-rose-500">📝 "{{ $order->catatan_pembeli }}"</p>
                                @endif
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 border-t border-gray-100 text-right">
                            <button wire:click="makananSiap({{ $order->id }})" class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black rounded-xl shadow-md transition-colors flex justify-center items-center gap-1.5">
                                Selesai Masak & Bungkus ➔
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-24 text-center bg-white border border-gray-200 rounded-2xl shadow-sm">
                        <div class="text-4xl mb-3">🍳</div>
                        <h4 class="font-black text-gray-900 text-sm">Dapur Sedang Santai</h4>
                        <p class="text-xs text-gray-400 mt-1">Belum ada pesanan kompor yang harus dinyalakan.</p>
                    </div>
                @endforelse
            </div>

        {{-- ================= TAB 3: SIAP DIAMBIL ================= --}}
        @elseif($activeTab === 'siap')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($this->siapDiambil as $order)
                    <div class="bg-[#059669] rounded-2xl shadow-lg text-white overflow-hidden flex flex-col justify-between relative min-h-[220px]">
                        <div class="absolute -right-6 -top-6 text-7xl opacity-10 pointer-events-none">🛍️</div>
                        
                        <div class="p-5">
                            <div class="border-b border-emerald-500/50 pb-3">
                                <span class="text-[10px] font-bold text-emerald-200 font-mono tracking-wider">{{ $order->order_id }}</span>
                                <h4 class="text-lg font-black text-white mt-0.5">{{ $order->mahasiswa->name ?? 'Mahasiswa' }}</h4>
                            </div>
                            
                            <div class="space-y-1.5 mt-4">
                                @foreach($order->items as $item)
                                    <div class="text-sm font-medium text-emerald-50">
                                        <span class="font-black mr-2 text-yellow-300">{{ $item->qty }}x</span>{{ $item->nama_produk_snapshot }}
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="p-4 bg-emerald-800/40 border-t border-emerald-500/30">
                            <button wire:click="serahkanMakanan({{ $order->id }})" wire:confirm="Konfirmasi serah terima barang online?" class="w-full py-3 bg-white text-emerald-700 text-xs font-black rounded-xl shadow-md hover:bg-emerald-50 transition-colors">
                                Serahkan ke Mahasiswa ✓
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-24 text-center bg-white border border-gray-200 rounded-2xl shadow-sm">
                        <div class="text-4xl mb-3">🛍️</div>
                        <h4 class="font-black text-gray-900 text-sm">Etalase Pengambilan Kosong</h4>
                        <p class="text-xs text-gray-400 mt-1">Belum ada makanan matang yang menunggu dijemput mahasiswa.</p>
                    </div>
                @endforelse
            </div>
        @endif

    </div>
</div>