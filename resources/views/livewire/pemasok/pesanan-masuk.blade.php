<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800">Pesanan Masuk</h1>
            <p class="text-sm font-bold text-gray-500 mt-1">Kelola pesanan dari jaringan Merchant Anda.</p>
        </div>
        
        <div class="w-full sm:w-auto relative">
            <input type="text" wire:model.live="search" placeholder="Cari ID Pesanan..." class="w-full sm:w-80 pl-10 pr-4 py-2.5 bg-white border-gray-200 rounded-2xl text-sm focus:ring-blue-500 shadow-sm font-bold text-gray-600">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="flex overflow-x-auto space-x-2 bg-gray-100 p-1.5 rounded-2xl w-full sm:w-max hide-scrollbar">
        <button wire:click="setTab('menunggu_lkbb')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'menunggu_lkbb' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Pesanan Baru
        </button>
        <button wire:click="setTab('diproses_pemasok')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'diproses_pemasok' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Diproses / Dikirim
        </button>
        <button wire:click="setTab('selesai')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'selesai' ? 'bg-white text-green-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Selesai
        </button>
    </div>

    <div class="space-y-4">
        @forelse($daftarPesanan as $order)
            @php
                $totalHargaPemasokIni = 0;
                $itemDipesan = [];

                foreach($order->details as $detail) {
                    if($detail->produkPemasok && $detail->produkPemasok->user_id == Auth::id()) {
                        $totalHargaPemasokIni += $detail->subtotal;
                        $itemDipesan[] = $detail->qty . 'x ' . $detail->nama_bahan_snapshot;
                    }
                }
                $stringItemDipesan = implode(', ', $itemDipesan);
            @endphp

        <div class="bg-white rounded-[24px] shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row gap-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            
            <div class="flex-1 space-y-4">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-black tracking-wider rounded-lg">{{ $order->nomor_order }}</span>
                    <span class="text-xs font-bold text-gray-400">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</span>
                    
                    @if($order->status === 'dikirim')
                        <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-md uppercase tracking-wider border border-blue-200">Sedang Dikirim</span>
                    @endif
                </div>
                
                <div>
                    <h3 class="text-lg font-black text-gray-800">Merchant/Kantin (ID: {{ $order->merchant_id }})</h3>
                    <p class="text-sm font-bold text-gray-500 mt-1 flex items-start gap-1.5">
                        Tgl. Kebutuhan: {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}
                    </p>
                    @if($order->catatan)
                        <p class="text-xs font-bold text-orange-500 mt-1">Catatan: {{ $order->catatan }}</p>
                    @endif
                </div>

                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-sm font-bold text-gray-600"><span class="text-gray-400 uppercase tracking-widest text-xs mb-1 block">Item Dipesan:</span> {{ $stringItemDipesan }}</p>
                </div>
            </div>

            <div class="flex flex-col justify-between items-start md:items-end border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6 min-w-[200px]">
                <div class="text-left md:text-right w-full mb-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Pesanan</p>
                    <h3 class="text-2xl font-black text-blue-600">Rp {{ number_format($totalHargaPemasokIni, 0, ',', '.') }}</h3>
                </div>

                <div class="w-full flex flex-col gap-2">
                    @if($activeTab === 'menunggu_lkbb')
                        <button wire:click="updateStatusPesanan({{ $order->id }}, 'diproses_pemasok')" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-xl hover:bg-blue-700 transition-all text-sm shadow-md shadow-blue-200">
                            Terima & Proses
                        </button>
                    @elseif($activeTab === 'diproses_pemasok')
                        @if($order->status === 'diproses_pemasok')
                            <button wire:click="updateStatusPesanan({{ $order->id }}, 'dikirim')" class="w-full bg-orange-500 text-white font-bold py-2.5 rounded-xl hover:bg-orange-600 transition-all text-sm shadow-md shadow-orange-200">
                                Tandai Dikirim
                            </button>
                        @elseif($order->status === 'dikirim')
                            <button wire:click="updateStatusPesanan({{ $order->id }}, 'selesai')" class="w-full bg-green-500 text-white font-bold py-2.5 rounded-xl hover:bg-green-600 transition-all text-sm shadow-md shadow-green-200">
                                Tandai Selesai
                            </button>
                        @endif
                    @elseif($activeTab === 'selesai')
                        <button class="w-full bg-gray-100 text-gray-500 font-bold py-2.5 rounded-xl text-sm cursor-not-allowed">
                            Selesai
                        </button>
                    @endif
                </div>
            </div>

        </div>
        @empty
        <div class="text-center py-16 bg-white rounded-[24px] border border-gray-100 border-dashed">
            <h3 class="text-lg font-black text-gray-800">Belum Ada Pesanan</h3>
            <p class="text-sm font-bold text-gray-500 mt-1">Tidak ada pesanan di kategori ini.</p>
        </div>
        @endforelse
        
        <div class="mt-4">
            {{ $daftarPesanan->links() }}
        </div>
    </div>

</div>