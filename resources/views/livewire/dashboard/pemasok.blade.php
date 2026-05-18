<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderDetail;
use App\Models\ProdukPemasok;

new 
#[Layout('layouts.app')] 
class extends Component {

    #[Computed]
    public function stats()
    {
        $pemasokId = Auth::id();

        // 1. Menghitung TOTAL MODAL (Uang untuk masak/belanja bahan)
        $totalModal = SupplyOrderDetail::whereHas('supplyOrder', function($q) use ($pemasokId) {
            $q->where('pemasok_id', $pemasokId)
              ->whereIn('status', ['diproses_pemasok', 'dikirim', 'selesai']);
        })->selectRaw('SUM(harga_modal_snapshot * qty) as total')->value('total') ?? 0;

        // 2. Menghitung TOTAL MARGIN (Untung murni yang bisa ditarik Pemasok)
        $totalMargin = SupplyOrderDetail::whereHas('supplyOrder', function($q) use ($pemasokId) {
            $q->where('pemasok_id', $pemasokId)
              ->whereIn('status', ['diproses_pemasok', 'dikirim', 'selesai']);
        })->selectRaw('SUM(margin_pemasok_snapshot * qty) as total')->value('total') ?? 0;

        return [
            'pesanan_baru' => SupplyOrder::where('pemasok_id', $pemasokId)->where('status', 'diproses_pemasok')->count(),
            'sedang_dikirim' => SupplyOrder::where('pemasok_id', $pemasokId)->where('status', 'dikirim')->count(),
            'pesanan_selesai' => SupplyOrder::where('pemasok_id', $pemasokId)->where('status', 'selesai')->count(),
            'total_modal' => $totalModal,
            'total_margin' => $totalMargin,
            'total_pendapatan' => $totalModal + $totalMargin,
            'total_produk' => ProdukPemasok::where('user_id', $pemasokId)->where('status', 'aktif')->count()
        ];
    }

    #[Computed]
    public function pesananTerbaru()
    {
        return SupplyOrder::with(['merchant.merchantProfile'])
            ->where('pemasok_id', Auth::id())
            ->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim', 'selesai'])
            ->latest()
            ->take(5)
            ->get();
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-gray-200 pb-5">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Dashboard Pemasok 👋</h2>
            <p class="text-gray-500 text-sm mt-1">Pantau omzet, pesanan masuk dari LKBB, dan atur logistik Anda.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('pemasok.inventaris') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition shadow-sm">
                Kelola Produk
            </a>
            <a href="{{ route('pemasok.pesanan-masuk') }}" wire:navigate class="px-5 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-blue-200 flex items-center gap-2 hover:bg-blue-700">
                Lihat Pesanan Baru
            </a>
        </div>
    </div>

    {{-- STATS GRID: DIPERBARUI DENGAN SPLIT MODAL & MARGIN --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        {{-- Card 1: Margin Keuntungan (Hak Tarik Tunai) --}}
        <div class="bg-gradient-to-br from-emerald-500 to-teal-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200/50 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-8 -mt-8 pointer-events-none"></div>
            <div class="relative z-10 flex flex-col h-full justify-between">
                <p class="text-emerald-100 text-[10px] font-extrabold tracking-widest mb-1">UNTUNG BERSIH (MARGIN)</p>
                <h3 class="text-3xl font-black tracking-tight truncate py-2">Rp {{ number_format($this->stats['total_margin'], 0, ',', '.') }}</h3>
                <a href="{{ route('pemasok.tarik-dana') }}" class="text-[10px] text-white underline underline-offset-2 font-bold hover:text-emerald-100">Bisa Ditarik (Withdraw) →</a>
            </div>
        </div>

        {{-- Card 2: Modal Produksi --}}
        <div class="bg-gradient-to-br from-blue-600 to-indigo-800 rounded-2xl p-5 text-white shadow-lg shadow-blue-200/50 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-8 -mt-8 pointer-events-none"></div>
            <div class="relative z-10 flex flex-col h-full justify-between">
                <p class="text-blue-100 text-[10px] font-extrabold tracking-widest mb-1">MODAL PRODUKSI (LKBB)</p>
                <h3 class="text-3xl font-black tracking-tight truncate py-2">Rp {{ number_format($this->stats['total_modal'], 0, ',', '.') }}</h3>
                <p class="text-[9px] text-blue-200 font-bold">*) Total dana kotor: Rp {{ number_format($this->stats['total_pendapatan'], 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Card 3: Pesanan Aktif (Baru + Sedang Jalan) --}}
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-amber-500 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <p class="text-[10px] text-gray-500 font-extrabold tracking-widest mb-1">PESANAN AKTIF</p>
                <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center">📦</div>
            </div>
            <h3 class="text-3xl font-black text-gray-900 truncate my-1">{{ $this->stats['pesanan_baru'] + $this->stats['sedang_dikirim'] }} <span class="text-sm text-gray-400 font-bold">PO</span></h3>
            <p class="text-[10px] font-bold text-amber-600">{{ $this->stats['pesanan_baru'] }} Siap dikirim • {{ $this->stats['sedang_dikirim'] }} Sedang jalan</p>
        </div>

        {{-- Card 4: Selesai & Total Produk --}}
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-emerald-500 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <p class="text-[10px] text-gray-500 font-extrabold tracking-widest mb-1">PESANAN SELESAI</p>
                <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center">✅</div>
            </div>
            <h3 class="text-3xl font-black text-gray-900 truncate my-1">{{ $this->stats['pesanan_selesai'] }} <span class="text-sm text-gray-400 font-bold">PO</span></h3>
            <p class="text-[10px] font-bold text-gray-400">Dari {{ $this->stats['total_produk'] }} produk aktif Anda.</p>
        </div>
    </div>

    {{-- TABEL PESANAN TERBARU --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col mt-6">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-black text-gray-900 text-sm">Pesanan Terbaru dari Kantin</h3>
            <a href="{{ route('pemasok.pengiriman') }}" wire:navigate class="text-[10px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-widest">Lihat Semua</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-white border-b border-gray-100 text-[10px] uppercase tracking-widest text-gray-400">
                        <th class="px-6 py-4 font-bold">Nomor PO</th>
                        <th class="px-6 py-4 font-bold">Penerima (Merchant)</th>
                        <th class="px-6 py-4 font-bold text-right">Nilai Pesanan</th>
                        <th class="px-6 py-4 font-bold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($this->pesananTerbaru as $order)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-black text-gray-800">{{ $order->nomor_order }}</div>
                                <div class="text-[10px] font-bold text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</div>
                                <div class="text-[10px] text-gray-500 mt-0.5">Blok: {{ $order->merchant->merchantProfile->lokasi_blok ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-black text-blue-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($order->status === 'menunggu_lkbb')
                                    <span class="inline-flex px-2 py-1 bg-gray-100 text-gray-600 text-[10px] font-extrabold uppercase tracking-wider rounded border border-gray-200">Review LKBB</span>
                                @elseif($order->status === 'diproses_pemasok')
                                    <span class="inline-flex px-2 py-1 bg-amber-50 text-amber-600 text-[10px] font-extrabold uppercase tracking-wider rounded border border-amber-200">Perlu Dikirim</span>
                                @elseif($order->status === 'dikirim')
                                    <span class="inline-flex px-2 py-1 bg-orange-50 text-orange-600 text-[10px] font-extrabold uppercase tracking-wider rounded border border-orange-200">Dalam Perjalanan</span>
                                @elseif($order->status === 'selesai')
                                    <span class="inline-flex px-2 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-extrabold uppercase tracking-wider rounded border border-emerald-200">Diterima</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-gray-400">
                                <p class="text-xs font-bold">Belum ada pesanan masuk.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>