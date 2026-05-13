<div class="p-6 max-w-7xl mx-auto space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Approval Pembiayaan PO</h1>
            <p class="text-sm font-medium text-gray-500 mt-1">Review permintaan stok dari Merchant dan cairkan dana ke Pemasok.</p>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="bg-indigo-50 border border-indigo-100 px-4 py-2 rounded-xl text-right">
                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">Brankas Investasi LKBB</p>
                <p class="text-lg font-black text-indigo-700">Rp {{ number_format($saldoInvestasi, 0, ',', '.') }}</p>
            </div>
            <div class="relative w-64">
                <input type="text" wire:model.live="search" placeholder="Cari No PO..." class="w-full pl-10 pr-4 py-2.5 bg-white border-gray-200 rounded-xl text-sm focus:ring-indigo-500 shadow-sm">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4">
        @forelse($orders as $order)
            <div class="bg-white rounded-[20px] shadow-sm border border-gray-100 p-5 flex flex-col md:flex-row items-center justify-between gap-6 hover:shadow-md transition">
                
                <div class="flex-1 w-full grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Info PO --}}
                    <div>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 border border-gray-200 text-[10px] font-black tracking-wider rounded-md">{{ $order->nomor_order }}</span>
                        <p class="text-[10px] font-bold text-gray-400 mt-2">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</p>
                        <p class="text-sm font-bold text-gray-800 mt-1">Item: {{ $order->details->count() }} Jenis Produk</p>
                        <p class="text-xs text-gray-500">Tgl Butuh: {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</p>
                    </div>

                    {{-- Info Rantai Pasok (Merchant -> Pemasok) --}}
                    <div class="col-span-2 flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <div class="flex-1 text-right">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pemohon (Merchant)</p>
                            <p class="text-sm font-black text-gray-800">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</p>
                        </div>
                        <div class="px-3 text-indigo-300">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Penyedia (Pemasok)</p>
                            <p class="text-sm font-black text-gray-800">{{ $order->pemasok->pemasokProfile->nama_perusahaan ?? $order->pemasok->name }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-auto flex flex-col md:items-end gap-3 border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6">
                    <div class="text-left md:text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Total Pendanaan</p>
                        <p class="text-xl font-black text-indigo-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>
                    <button wire:click="bukaModal({{ $order->id }})" class="w-full md:w-auto bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold px-6 py-2 rounded-xl text-sm hover:bg-indigo-100 transition shadow-sm">
                        Review & Cairkan
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-20 bg-white rounded-[24px] border border-gray-100 shadow-sm">
                <div class="text-4xl mb-3">☕</div>
                <h3 class="text-lg font-black text-gray-800">Tidak Ada Antrean PO</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Semua permintaan pendanaan sudah diproses.</p>
            </div>
        @endforelse
        
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>

    {{-- MODAL REVIEW DETAIL --}}
    @if($showModal && $selectedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-[24px] shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
                
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="font-black text-gray-900 text-lg">Review Pendanaan PO</h3>
                        <p class="text-xs text-gray-500 font-bold mt-0.5">{{ $selectedOrder->nomor_order }}</p>
                    </div>
                    <button wire:click="tutupModal" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    {{-- Detail Hubungan --}}
                    <div class="grid grid-cols-2 gap-4 bg-indigo-50 border border-indigo-100 rounded-xl p-4">
                        <div>
                            <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-1">Pemohon (Kantin)</p>
                            <p class="text-sm font-black text-indigo-900">{{ $selectedOrder->merchant->merchantProfile->nama_kantin ?? $selectedOrder->merchant->name }}</p>
                            <p class="text-xs font-medium text-indigo-700 mt-0.5">{{ $selectedOrder->merchant->merchantProfile->nama_pemilik ?? '' }}</p>
                        </div>
                        <div class="border-l border-indigo-200 pl-4">
                            <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-1">Penerima Dana (Pemasok)</p>
                            <p class="text-sm font-black text-indigo-900">{{ $selectedOrder->pemasok->pemasokProfile->nama_perusahaan ?? $selectedOrder->pemasok->name }}</p>
                            <p class="text-xs font-medium text-indigo-700 mt-0.5">Tujuan TF: {{ $selectedOrder->pemasok->pemasokProfile->info_bank ?? 'Belum diset' }}</p>
                        </div>
                    </div>

                    {{-- Tabel Item --}}
                    <div>
                        <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Rincian Barang yang Dipesan</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[10px] text-gray-400 uppercase tracking-widest">
                                    <th class="pb-2">Produk</th>
                                    <th class="pb-2 text-center">Qty</th>
                                    <th class="pb-2 text-right">Harga (Modal+Margin)</th>
                                    <th class="pb-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-gray-700 font-medium">
                                @foreach($selectedOrder->details as $item)
                                    <tr>
                                        <td class="py-2">{{ $item->nama_produk_snapshot }}</td>
                                        <td class="py-2 text-center font-bold">{{ $item->qty }}</td>
                                        <td class="py-2 text-right">Rp {{ number_format(($item->harga_modal_snapshot + $item->margin_pemasok_snapshot), 0, ',', '.') }}</td>
                                        <td class="py-2 text-right font-bold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-gray-200">
                                <tr>
                                    <td colspan="3" class="py-3 text-right font-black text-gray-600">TOTAL PENDANAAN:</td>
                                    <td class="py-3 text-right font-black text-indigo-600 text-lg">Rp {{ number_format($selectedOrder->total_estimasi, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Form Penolakan (Opsional) --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2">Alasan Penolakan (Isi hanya jika ingin menolak)</label>
                        <textarea wire:model="alasanPenolakan" rows="2" class="w-full border-gray-200 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-sm" placeholder="Contoh: Saldo sedang menipis, atau kuota kantin ini habis..."></textarea>
                        @error('alasanPenolakan') <span class="text-xs text-rose-500 font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-between gap-4">
                    <button wire:click="tolakPendanaan" wire:confirm="Yakin ingin menolak pengajuan PO ini?" class="px-6 py-2.5 bg-white border border-rose-200 text-rose-600 font-bold rounded-xl hover:bg-rose-50 transition shadow-sm">
                        Tolak PO
                    </button>
                    <button wire:click="setujuiPendanaan" wire:confirm="Dana akan langsung ditransfer ke Pemasok. Lanjutkan?" class="flex-1 px-6 py-2.5 bg-indigo-600 text-white font-black rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Setujui & Cairkan Rp {{ number_format($selectedOrder->total_estimasi, 0, ',', '.') }}
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>