<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6 relative">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-5">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Pesanan Masuk (Inbox PO)</h1>
            <p class="text-sm font-medium text-gray-500 mt-1">Review pesanan dari Kantin/Merchant. Setujui agar LKBB dapat mencairkan dananya.</p>
        </div>
        
        <div class="w-full sm:w-auto relative">
            <input type="text" wire:model.live="search" placeholder="Cari PO atau Nama Kantin..." class="w-full sm:w-80 pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 shadow-sm font-bold text-gray-700 transition">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($daftarPesanan as $order)
        <div class="bg-white rounded-[20px] shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
            
            {{-- Header Card --}}
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-gray-50 border border-gray-200 text-gray-700 text-[10px] font-black tracking-wider rounded-lg">{{ $order->nomor_order }}</span>
                    <span class="text-xs font-bold text-gray-400">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y - H:i') }}</span>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold text-gray-500">Nilai Pendanaan:</span>
                    <span class="text-sm font-black text-blue-600 ml-1">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</span>
                </div>
            </div>
            
            <div class="flex flex-col lg:flex-row gap-6">
                {{-- Info Pemesan (Kantin) --}}
                <div class="flex-1 flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0 border border-blue-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Pemesan (Kantin)</p>
                        <h3 class="text-lg font-black text-gray-900">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</h3>
                        
                        <div class="mt-2 space-y-1">
                            <p class="text-xs font-medium text-gray-600">
                                Blok: <span class="font-bold">{{ $order->merchant->merchantProfile->lokasi_blok ?? 'Belum diatur' }}</span>
                            </p>
                            <p class="text-xs font-medium text-gray-600">
                                Item Dipesan: <span class="font-bold">{{ $order->details->count() }} Jenis Produk</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Status & Aksi Kanan --}}
                <div class="flex flex-col justify-end lg:items-end gap-2 lg:w-72 border-t lg:border-t-0 lg:border-l border-gray-100 pt-4 lg:pt-0 lg:pl-6">
                    
                    @if($order->status === 'menunggu_pemasok')
                        <div class="w-full text-left lg:text-right mb-2 bg-indigo-50 p-2 rounded-lg border border-indigo-100">
                            <span class="text-[10px] font-extrabold text-indigo-600 uppercase tracking-wider">⚠️ Perlu Konfirmasi Anda</span>
                        </div>
                        <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-200 transition-all text-sm">
                            Review & Konfirmasi
                        </button>

                    @elseif($order->status === 'menunggu_lkbb')
                        <div class="w-full text-left lg:text-right mb-2 bg-amber-50 p-2 rounded-lg border border-amber-100">
                            <span class="text-[10px] font-extrabold text-amber-600 uppercase tracking-wider">⏳ Menunggu Pencairan LKBB</span>
                        </div>
                        <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all text-sm">
                            Lihat Pesanan
                        </button>

                    {{-- ==================================================== --}}
                    {{-- UPDATE DESAIN DANA CAIR PERSIS SEPERTI GAMBAR        --}}
                    {{-- ==================================================== --}}
                    @elseif($order->status === 'diproses_pemasok')
                        <div class="w-full flex flex-col gap-2.5">
                            {{-- Badge Dana Cair Style Baru --}}
                            <div class="w-full bg-gradient-to-r from-[#10b981] to-[#0f766e] p-3.5 rounded-2xl shadow-lg shadow-emerald-200/50 flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-full bg-black/20 flex items-center justify-center shrink-0 shadow-inner">
                                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-xl font-black text-white tracking-wide leading-none mb-1">DANA CAIR</h4>
                                    <p class="text-[10px] font-medium text-emerald-50 leading-tight">Lanjut ke menu Pengiriman Logistik.</p>
                                </div>
                            </div>
                            
                            {{-- Tombol Lihat Rincian Style Baru --}}
                            <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full bg-white border border-gray-200 text-[#0f766e] font-black py-3 rounded-2xl hover:bg-gray-50 hover:border-gray-300 transition-all text-sm shadow-sm">
                                Lihat Rincian
                            </button>
                        </div>
                    {{-- ==================================================== --}}
                    
                    @elseif($order->status === 'ditolak')
                        <div class="w-full text-left lg:text-right mb-2 bg-rose-50 p-2 rounded-lg border border-rose-100">
                            <span class="text-[10px] font-extrabold text-rose-600 uppercase tracking-wider">❌ Ditolak / Batal</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-20 bg-white rounded-[24px] border border-gray-100 shadow-sm">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
            </div>
            <h3 class="text-lg font-black text-gray-800">Belum Ada Pesanan Masuk</h3>
            <p class="text-sm font-medium text-gray-500 mt-1 max-w-sm text-center">Permintaan order dari Merchant akan muncul di sini.</p>
        </div>
        @endforelse
        
        <div class="mt-6">
            {{ $daftarPesanan->links() }}
        </div>
    </div>

    {{-- MODAL LIHAT DETAIL & KONFIRMASI --}}
    <div x-data="{ open: @entangle('showModalDetail') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"></div>
        <div x-show="open" class="bg-white rounded-[24px] shadow-2xl overflow-hidden w-full max-w-2xl z-50 relative max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                <div>
                    <h3 class="text-lg font-black text-gray-800">Review Pesanan Merchant</h3>
                    <p class="text-xs font-bold text-gray-500 mt-0.5">PO: {{ $this->selectedOrder->nomor_order ?? '' }}</p>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-red-500 bg-white rounded-full p-1 border border-gray-200"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                @if($this->selectedOrder)
                <div class="mb-6 grid grid-cols-2 gap-4 bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <div>
                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Pemesan (Kantin)</p>
                        <p class="text-sm font-black text-gray-900">{{ $this->selectedOrder->merchant->merchantProfile->nama_kantin ?? $this->selectedOrder->merchant->name }}</p>
                        <p class="text-xs font-medium text-gray-700 mt-1">{{ $this->selectedOrder->merchant->merchantProfile->nama_pemilik ?? '-' }} ({{ $this->selectedOrder->merchant->merchantProfile->no_hp ?? '-' }})</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Diminta Tgl</p>
                        <p class="text-xs font-bold text-gray-700">{{ \Carbon\Carbon::parse($this->selectedOrder->tanggal_kebutuhan)->format('d F Y') }}</p>
                        
                        @if($this->selectedOrder->catatan)
                            <div class="mt-2 text-[10px] font-bold text-yellow-700 bg-yellow-100 p-1.5 rounded">
                                Catatan: {{ $this->selectedOrder->catatan }}
                            </div>
                        @endif
                    </div>
                </div>

                <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Daftar Kebutuhan Stok</h4>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[10px] text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 font-bold">Nama Produk</th>
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
                                <td colspan="3" class="px-4 py-3 text-right font-black text-gray-600 text-xs uppercase">Estimasi Pencairan:</td>
                                <td class="px-4 py-3 text-right font-black text-blue-600 text-base">Rp {{ number_format($this->selectedOrder->total_estimasi, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>

            <div class="p-4 border-t border-gray-100 bg-white shrink-0 flex justify-end gap-3">
                @if($this->selectedOrder && $this->selectedOrder->status === 'menunggu_pemasok')
                    <button wire:click="bukaModalTolak" class="px-6 py-2.5 bg-white border border-red-200 text-red-600 font-bold rounded-xl text-sm hover:bg-red-50 transition shadow-sm">Tolak Pesanan</button>
                    <button wire:click="setujuiPesanan" class="px-6 py-2.5 bg-blue-600 text-white font-black rounded-xl text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Setujui & Teruskan ke LKBB
                    </button>
                @else
                    <button @click="open = false" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl text-sm hover:bg-gray-200">Tutup Form</button>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL TOLAK PESANAN --}}
    <div x-data="{ open: @entangle('showModalTolak') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"></div>
        <div x-show="open" class="bg-white rounded-[24px] shadow-2xl overflow-hidden w-full max-w-md z-50 relative">
            <div class="p-6 border-b border-gray-100 bg-red-50 text-red-700 flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h3 class="text-lg font-black">Tolak Pesanan</h3>
            </div>
            
            <form wire:submit.prevent="tolakPesanan" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Berikan Alasan Penolakan</label>
                    <textarea wire:model="alasanPenolakan" rows="3" placeholder="Contoh: Maaf, stok ayam sedang kosong hari ini..." class="w-full rounded-xl border-gray-200 text-sm focus:ring-red-500 resize-none"></textarea>
                    @error('alasanPenolakan') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="tutupModal" class="flex-1 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl text-sm hover:bg-gray-200">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-red-600 text-white font-black rounded-xl text-sm shadow-md hover:bg-red-700">Konfirmasi Tolak</button>
                </div>
            </form>
        </div>
    </div>

</div>