<div class="p-6 relative max-w-7xl mx-auto">
    
    {{-- CSS Khusus Cetak --}}
    <style>
        @media print {
            body * { visibility: hidden; }
            #area-cetak-label, #area-cetak-label * { visibility: visible; }
            #area-cetak-label { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>

    {{-- Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pengiriman & Logistik</h1>
            <p class="text-sm text-gray-500 mt-1">Atur armada, cetak surat jalan, dan kirim barang ke Merchant.</p>
        </div>
        
        <div class="relative group">
            <input wire:model.live="search" type="text" placeholder="Cari PO atau Nama Kantin..." 
                   class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none w-full sm:w-72 transition-all bg-white shadow-sm font-bold text-gray-700">
            <div class="absolute left-3 top-3 text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex overflow-x-auto space-x-2 bg-white p-1.5 rounded-2xl w-full sm:w-max mb-6 border border-gray-100 shadow-sm scrollbar-hide">
        <button wire:click="setTab('diproses_pemasok')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all relative {{ $activeTab === 'diproses_pemasok' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50' }}">
            📦 Perlu Dikirim 
            @if($countPerluDikirim > 0)
                <span class="ml-1 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full">{{ $countPerluDikirim }}</span>
            @endif
        </button>
        <button wire:click="setTab('dikirim')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'dikirim' ? 'bg-orange-50 text-orange-600' : 'text-gray-500 hover:bg-gray-50' }}">
            🚚 Sedang Jalan
        </button>
        <button wire:click="setTab('selesai')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'selesai' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-500 hover:bg-gray-50' }}">
            ✅ Diterima Merchant
        </button>
    </div>

    {{-- List Pengiriman --}}
    <div class="space-y-4">
        @forelse($orders as $order)
        <div class="bg-white rounded-[20px] border border-gray-100 shadow-sm p-5 hover:shadow-md transition">
            
            {{-- Header Card --}}
            <div class="flex flex-wrap items-center justify-between border-b border-gray-100 pb-3 mb-4 gap-2">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-gray-50 border border-gray-200 text-gray-700 text-[10px] font-black tracking-wider rounded-lg">{{ $order->nomor_order }}</span>
                    <span class="text-xs font-bold text-gray-400">Tgl Butuh: {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</span>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold text-gray-500">Nilai Pesanan:</span>
                    <span class="text-sm font-black text-blue-600 ml-1">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</span>
                </div>
            </div>
            
            <div class="flex flex-col lg:flex-row gap-6">
                {{-- Info Penerima --}}
                <div class="flex-1 flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0 border border-blue-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Tujuan Pengiriman</p>
                        <h3 class="text-lg font-black text-gray-900">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</h3>
                        
                        <div class="mt-2 space-y-1">
                            <p class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Blok/Lokasi: <span class="font-bold">{{ $order->merchant->merchantProfile->lokasi_blok ?? 'Belum diatur' }}</span>
                            </p>
                            <p class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                Penerima: <span class="font-bold">{{ $order->merchant->merchantProfile->nama_pemilik ?? '-' }} ({{ $order->merchant->merchantProfile->no_hp ?? '-' }})</span>
                            </p>
                        </div>

                        @if($order->catatan)
                            <div class="mt-3 p-2 bg-yellow-50 border border-yellow-100 rounded-lg inline-block">
                                <p class="text-[10px] font-bold text-yellow-700">Catatan: {{ $order->catatan }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Aksi Kanan --}}
                <div class="flex flex-col justify-end lg:items-end gap-2 lg:w-64 border-t lg:border-t-0 lg:border-l border-gray-100 pt-4 lg:pt-0 lg:pl-6">
                    @if($activeTab === 'diproses_pemasok')
                        <button wire:click="bukaModalAtur({{ $order->id }})" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-200 transition-all text-sm">
                            Kirim Barang
                        </button>
                        <div class="flex gap-2 w-full">
                            <button wire:click="bukaModalDetail({{ $order->id }})" class="flex-1 bg-white border border-blue-200 text-blue-600 font-bold py-2 rounded-xl hover:bg-blue-50 transition-all text-xs">
                                Lihat Detail
                            </button>
                            <button wire:click="cetakLabel({{ $order->id }})" class="flex-1 bg-white border border-gray-200 text-gray-600 font-bold py-2 rounded-xl hover:bg-gray-50 transition-all text-xs">
                                Cetak Label
                            </button>
                        </div>
                    @elseif($activeTab === 'dikirim')
                        <div class="w-full text-left lg:text-right mb-2 bg-orange-50 p-2 rounded-lg border border-orange-100">
                            <span class="text-[10px] font-extrabold text-orange-600 uppercase tracking-wider">📦 Sedang Dikirim</span>
                        </div>
                        <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all text-sm">
                            Lihat Pesanan
                        </button>
                    @else
                        <div class="w-full text-left lg:text-right mb-2 bg-emerald-50 p-2 rounded-lg border border-emerald-100">
                            <span class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-wider">✅ Diterima Merchant</span>
                            <p class="text-[9px] text-emerald-700 font-bold mt-1">{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y H:i') }}</p>
                        </div>
                        <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all text-sm">
                            Cek Rincian
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            </div>
            <h3 class="text-lg font-black text-gray-800">Tidak ada pengiriman</h3>
            <p class="text-sm font-bold text-gray-500 mt-1">Data pesanan di kategori ini masih kosong.</p>
        </div>
        @endforelse

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>

    {{-- MODAL LIHAT DETAIL PESANAN --}}
    <div x-data="{ open: @entangle('showModalDetail') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm no-print"></div>
        <div x-show="open" class="bg-white rounded-[24px] shadow-2xl overflow-hidden w-full max-w-2xl z-50 relative max-h-[90vh] flex flex-col no-print">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                <div>
                    <h3 class="text-lg font-black text-gray-800">Detail Pesanan Merchant</h3>
                    <p class="text-xs font-bold text-gray-500 mt-0.5">PO: {{ $this->selectedOrder->nomor_order ?? '' }}</p>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-red-500 bg-white rounded-full p-1 border border-gray-200"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                @if($this->selectedOrder)
                <div class="mb-6 grid grid-cols-2 gap-4 bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <div>
                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Penerima</p>
                        <p class="text-sm font-black text-gray-900">{{ $this->selectedOrder->merchant->merchantProfile->nama_kantin ?? $this->selectedOrder->merchant->name }}</p>
                        <p class="text-xs font-medium text-gray-700 mt-1">{{ $this->selectedOrder->merchant->merchantProfile->nama_pemilik ?? '-' }} ({{ $this->selectedOrder->merchant->merchantProfile->no_hp ?? '-' }})</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Lokasi</p>
                        <p class="text-xs font-bold text-gray-700">{{ $this->selectedOrder->merchant->merchantProfile->lokasi_blok ?? 'Belum diatur' }}</p>
                        
                        @if($this->selectedOrder->catatan)
                            <div class="mt-2 text-[10px] font-bold text-yellow-700 bg-yellow-100 p-1.5 rounded">
                                Catatan: {{ $this->selectedOrder->catatan }}
                            </div>
                        @endif
                    </div>
                </div>

                <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Rincian Barang</h4>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[10px] text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 font-bold">Produk</th>
                                <th class="px-4 py-3 font-bold text-center">Qty</th>
                                <th class="px-4 py-3 font-bold text-right">Harga Total</th>
                                <th class="px-4 py-3 font-bold text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($this->selectedOrder->details as $item)
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-800">{{ $item->nama_produk_snapshot }}</td>
                                <td class="px-4 py-3 text-center font-black">{{ $item->qty }}</td>
                                <td class="px-4 py-3 text-right text-xs text-gray-600">Rp {{ number_format(($item->harga_modal_snapshot + $item->margin_pemasok_snapshot), 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-black text-gray-600 text-xs uppercase">Total Pendapatan:</td>
                                <td class="px-4 py-3 text-right font-black text-blue-600 text-base">Rp {{ number_format($this->selectedOrder->total_estimasi, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>

            <div class="p-4 border-t border-gray-100 bg-white shrink-0 flex justify-end">
                <button @click="open = false" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl text-sm hover:bg-gray-200">Tutup</button>
            </div>
        </div>
    </div>

    {{-- MODAL ATUR PENGIRIMAN --}}
    <div x-data="{ open: @entangle('showModalAtur') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm no-print"></div>
        <div x-show="open" class="bg-white rounded-[24px] shadow-2xl overflow-hidden w-full max-w-lg z-50 relative no-print">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-black text-gray-800">Kirim Barang</h3>
                <button @click="open = false" class="text-gray-400 hover:text-red-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            @if($this->selectedOrder)
            <form wire:submit.prevent="simpanPengiriman" class="p-6 space-y-4">
                <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 mb-4">
                    <p class="text-xs font-bold text-blue-600 uppercase">Tujuan Pengiriman</p>
                    <p class="text-sm font-black text-gray-900 mt-1">{{ $this->selectedOrder->merchant->merchantProfile->nama_kantin ?? $this->selectedOrder->merchant->name }}</p>
                    <p class="text-xs font-medium text-gray-600 mt-0.5">Blok: {{ $this->selectedOrder->merchant->merchantProfile->lokasi_blok ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Kurir</label>
                    <input type="text" wire:model="nama_kurir"
                           placeholder="cth: Budi Santoso"
                           class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500 font-bold bg-gray-50">
                    @error('nama_kurir') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">No HP Kurir</label>
                    <input type="tel" wire:model="no_hp_kurir"
                           placeholder="cth: 081234567890"
                           inputmode="numeric"
                           class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500 font-bold bg-gray-50">
                    @error('no_hp_kurir') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Resi / Surat Jalan</label>
                    <input type="text" wire:model="no_resi" class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500 font-bold bg-gray-50">
                    @error('no_resi') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
                <div class="pt-4 flex gap-3">
                    <button type="button" @click="open = false" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl text-sm hover:bg-gray-200">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white font-black rounded-xl text-sm shadow-md hover:bg-blue-700">Tandai Dikirim</button>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- MODAL CETAK SURAT JALAN --}}
    <div x-data="{ open: @entangle('showModalCetak') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm no-print"></div>
        <div x-show="open" class="bg-white rounded-[24px] shadow-2xl overflow-hidden w-full max-w-lg z-50 relative">
            <div id="area-cetak-label" class="p-8 bg-white text-black">
                @if($this->selectedOrder)
                <div class="border-2 border-black p-4 rounded-lg relative">
                    <div class="text-center border-b-2 border-black pb-4 mb-4">
                        <h2 class="text-xl font-black uppercase tracking-widest">SURAT JALAN / DELIVERY ORDER</h2>
                        <p class="text-xs font-bold mt-1">Order ID: {{ $this->selectedOrder->nomor_order }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs uppercase text-gray-500 mb-1">Dari Pemasok:</p>
                            <p class="font-bold text-sm">{{ Auth::user()->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500 mb-1">Kepada Merchant:</p>
                            <p class="font-bold text-sm">{{ $this->selectedOrder->merchant->merchantProfile->nama_kantin ?? $this->selectedOrder->merchant->name }}</p>
                            <p class="text-xs">{{ $this->selectedOrder->merchant->merchantProfile->lokasi_blok ?? '' }}</p>
                            <p class="text-xs">{{ $this->selectedOrder->merchant->merchantProfile->no_hp ?? '' }}</p>
                        </div>
                    </div>

                    <div class="border-t-2 border-black pt-4 mb-4">
                        <p class="text-xs uppercase text-gray-500 mb-1">Diantar Oleh:</p>
                        <p class="font-bold text-sm">{{ $this->selectedOrder->nama_kurir ?? '-' }}</p>
                        <p class="text-xs">HP: {{ $this->selectedOrder->no_hp_kurir ?? '-' }}</p>
                        <p class="text-xs">Resi: {{ $this->selectedOrder->no_resi ?? '-' }}</p>
                    </div>

                    <div class="border-t-2 border-black pt-4">
                        <p class="text-xs uppercase text-gray-500 mb-2">Rincian Barang:</p>
                        <table class="w-full text-xs text-left mb-4">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="pb-1">Nama Barang</th>
                                    <th class="pb-1 text-center">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->selectedOrder->details as $item)
                                <tr>
                                    <td class="py-1">{{ $item->nama_produk_snapshot }}</td>
                                    <td class="py-1 text-center font-bold">{{ $item->qty }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-between text-xs text-center pt-4 border-t border-dashed border-gray-400">
                        <div>
                            <p class="mb-8">Ttd. Pengirim</p>
                            <p class="font-bold">( ......................... )</p>
                        </div>
                        <div>
                            <p class="mb-8">Ttd. Penerima</p>
                            <p class="font-bold">( ......................... )</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-100 flex gap-3 no-print">
                <button @click="open = false" class="flex-1 px-4 py-2.5 bg-white border border-gray-200 text-gray-600 font-bold rounded-xl text-sm hover:bg-gray-100">Tutup</button>
                <button onclick="window.print()" class="flex-1 px-4 py-2.5 bg-blue-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-blue-700 flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Surat Jalan
                </button>
            </div>
        </div>
    </div>

</div>