<div class="p-6 max-w-7xl mx-auto space-y-6 relative">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Approval Pembiayaan PO</h1>
            <p class="text-sm font-medium text-gray-500 mt-1">Review permintaan stok dari Merchant dan cairkan dana ke Pemasok.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full sm:w-auto">
            <div class="bg-[#4338CA] px-5 py-2.5 rounded-xl text-right w-full sm:w-auto shadow-md">
                <p class="text-[10px] font-extrabold text-indigo-200 uppercase tracking-widest">Saldo Brankas Investasi</p>
                <p class="text-xl font-black text-white">Rp {{ number_format($saldoInvestasi, 0, ',', '.') }}</p>
            </div>
            <div class="relative w-full sm:w-64">
                <input type="text" wire:model.live="search" placeholder="Cari Kantin atau PO..." class="w-full pl-10 pr-4 py-2.5 bg-white border-gray-200 rounded-xl text-sm focus:ring-[#4338CA] focus:border-[#4338CA] shadow-sm font-bold">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4">
        @forelse($orders as $order)
            <div class="bg-white rounded-[20px] shadow-sm border border-gray-100 p-5 flex flex-col md:flex-row items-center justify-between gap-6 hover:shadow-md transition">
                
                <div class="flex-1 w-full grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Info PO --}}
                    <div>
                        <span class="px-2 py-1 bg-indigo-50 text-[#4338CA] border border-indigo-100 text-[10px] font-black tracking-wider rounded-md">{{ $order->nomor_order }}</span>
                        <p class="text-[10px] font-bold text-gray-400 mt-2">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</p>
                        <p class="text-sm font-bold text-gray-800 mt-1">Item: {{ $order->details->count() }} Jenis Produk</p>
                        <p class="text-[10px] text-gray-500 font-bold bg-gray-50 p-1 rounded inline-block mt-1">Tgl Butuh: {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</p>
                    </div>

                    {{-- Info Rantai Pasok (Merchant -> Pemasok) --}}
                    <div class="col-span-2 flex flex-col sm:flex-row sm:items-center gap-4 bg-gray-50/80 p-3 rounded-xl border border-gray-100">
                        <div class="flex-1 text-left sm:text-right">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Pemohon (Kantin)</p>
                            <p class="text-sm font-black text-gray-800">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</p>
                            <p class="text-[10px] font-medium text-gray-500 truncate">{{ $order->merchant->merchantProfile->nama_pemilik ?? '-' }}</p>
                        </div>
                        <div class="px-3 text-[#4338CA] hidden sm:block">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                        </div>
                        <div class="px-3 text-[#4338CA] sm:hidden flex justify-center">
                            <svg class="w-5 h-5 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Penyedia (Pemasok)</p>
                            <p class="text-sm font-black text-gray-800">{{ $order->pemasok->pemasokProfile->nama_perusahaan ?? $order->pemasok->name }}</p>
                            <p class="text-[10px] font-medium text-emerald-600 font-bold truncate">Telah menyetujui pesanan ✅</p>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-auto flex flex-col md:items-end gap-3 border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6 shrink-0">
                    <div class="text-left md:text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Total Pendanaan</p>
                        <p class="text-xl font-black text-[#4338CA]">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>
                    <button wire:click="bukaModal({{ $order->id }})" class="w-full md:w-auto bg-[#4338CA] text-white font-bold px-6 py-2.5 rounded-xl text-sm hover:bg-indigo-800 transition shadow-md shadow-indigo-200">
                        Review & Cairkan
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-20 bg-white rounded-[24px] border border-gray-100 shadow-sm flex flex-col items-center">
                <div class="text-5xl mb-4 opacity-50">☕</div>
                <h3 class="text-xl font-black text-gray-800">Tidak Ada Antrean PO</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Semua permintaan pendanaan dari Merchant sudah diproses.</p>
            </div>
        @endforelse
        
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>

    {{-- MODAL REVIEW DETAIL --}}
    @if($showModal && $selectedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="tutupModal"></div>
            <div class="bg-white rounded-[24px] shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh] z-10">
                
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 shrink-0">
                    <div>
                        <h3 class="font-black text-gray-900 text-lg">Review Pendanaan PO</h3>
                        <p class="text-xs text-gray-500 font-bold mt-0.5">{{ $selectedOrder->nomor_order }}</p>
                    </div>
                    <button wire:click="tutupModal" class="text-gray-400 hover:text-gray-600 transition bg-white border border-gray-200 rounded-full p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    {{-- Detail Hubungan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-indigo-50 border border-indigo-100 rounded-xl p-5">
                        <div>
                            <p class="text-[10px] font-bold text-[#4338CA] uppercase tracking-widest mb-1">Pemohon (Kantin)</p>
                            <p class="text-sm font-black text-gray-900">{{ $selectedOrder->merchant->merchantProfile->nama_kantin ?? $selectedOrder->merchant->name }}</p>
                            <p class="text-xs font-bold text-gray-600 mt-0.5">{{ $selectedOrder->merchant->merchantProfile->nama_pemilik ?? '' }}</p>
                            <p class="text-[10px] text-gray-500 font-medium mt-1">Lokasi: {{ $selectedOrder->merchant->merchantProfile->lokasi_blok ?? '-' }}</p>
                        </div>
                        <div class="sm:border-l border-t sm:border-t-0 border-indigo-200 sm:pl-5 pt-4 sm:pt-0">
                            <p class="text-[10px] font-bold text-[#4338CA] uppercase tracking-widest mb-1">Penerima Dana (Pemasok)</p>
                            <p class="text-sm font-black text-gray-900">{{ $selectedOrder->pemasok->pemasokProfile->nama_perusahaan ?? $selectedOrder->pemasok->name }}</p>
                            <p class="text-xs font-bold text-gray-600 mt-0.5">Tujuan Rekening:</p>
                            <p class="text-xs font-mono text-gray-800 bg-white p-1 rounded inline-block mt-0.5 border border-indigo-100">{{ $selectedOrder->pemasok->pemasokProfile->info_bank ?? 'Belum diset' }}</p>
                        </div>
                    </div>

                    {{-- Tabel Item --}}
                    <div>
                        <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Rincian Barang yang Dipesan</h4>
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-[10px] text-gray-500 uppercase tracking-widest">
                                        <th class="px-4 py-3 font-bold">Produk</th>
                                        <th class="px-4 py-3 text-center font-bold">Qty</th>
                                        <th class="px-4 py-3 text-right font-bold">Harga (Modal+Margin)</th>
                                        <th class="px-4 py-3 text-right font-bold">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-800 font-medium">
                                    @foreach($selectedOrder->details as $item)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="px-4 py-3 font-bold">{{ $item->nama_produk_snapshot }}</td>
                                            <td class="px-4 py-3 text-center font-black">{{ $item->qty }}</td>
                                            <td class="px-4 py-3 text-right text-xs">Rp {{ number_format(($item->harga_modal_snapshot + $item->margin_pemasok_snapshot), 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right font-black">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 border-t border-gray-200">
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-right font-black text-gray-600 text-xs">TOTAL PENDANAAN:</td>
                                        <td class="px-4 py-4 text-right font-black text-[#4338CA] text-lg">Rp {{ number_format($selectedOrder->total_estimasi, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Form Penolakan (Opsional) --}}
                    <div class="bg-rose-50/50 p-4 rounded-xl border border-rose-100">
                        <label class="block text-[10px] uppercase font-bold text-rose-600 mb-1.5 tracking-wider">Alasan Penolakan (Opsional)</label>
                        <textarea wire:model="alasanPenolakan" rows="2" class="w-full border-rose-200 bg-white rounded-xl focus:ring-rose-500 focus:border-rose-500 text-sm" placeholder="Isi hanya jika ingin menolak PO ini. (Contoh: Saldo brankas kurang...)"></textarea>
                        @error('alasanPenolakan') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 bg-white flex flex-col sm:flex-row justify-between gap-3 shrink-0">
                    <button wire:click="tolakPendanaan" wire:confirm="Yakin ingin membatalkan dan menolak pengajuan PO ini?" wire:loading.attr="disabled" class="px-6 py-3 bg-white border border-rose-200 text-rose-600 font-bold rounded-xl hover:bg-rose-50 transition shadow-sm disabled:opacity-50">
                        Tolak PO
                    </button>
                    <button wire:click="setujuiPendanaan" wire:confirm="Dana akan dipotong dari Brankas dan ditransfer ke Pemasok. Lanjutkan?" wire:loading.attr="disabled" class="flex-1 px-6 py-3 bg-[#4338CA] text-white font-black rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-800 transition flex items-center justify-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="setujuiPendanaan">Setujui & Cairkan Rp {{ number_format($selectedOrder->total_estimasi, 0, ',', '.') }}</span>
                        <span wire:loading wire:target="setujuiPendanaan">Memproses Pencairan...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>