<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-5">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Purchase Order (PO) Masuk</h1>
            <p class="text-sm font-medium text-gray-500 mt-1">Pantau pesanan baru dari Merchant. Tunggu pencairan dana LKBB sebelum menyiapkan barang.</p>
        </div>
        
        <div class="w-full sm:w-auto relative">
            <input type="text" wire:model.live="search" placeholder="Cari Nomor PO..." class="w-full sm:w-80 pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 shadow-sm font-bold text-gray-700 transition">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($daftarPesanan as $order)
            @php
                // Menghitung total nilai PO yang masuk ke kantong Pemasok ini
                $totalNilaiPO = 0;
                $itemDipesan = [];

                foreach($order->details as $detail) {
                    $totalNilaiPO += $detail->subtotal; // Subtotal sudah mengandung Modal + Margin dari tabel snapshot
                    $itemDipesan[] = '<span class="font-bold text-gray-800">' . $detail->qty . 'x</span> ' . $detail->nama_produk_snapshot;
                }
                $stringItemDipesan = implode(', ', $itemDipesan);
            @endphp

        <div class="bg-white rounded-[20px] shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row gap-6 hover:shadow-md transition-shadow">
            
            <div class="flex-1 space-y-4">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-gray-50 text-gray-600 border border-gray-200 text-xs font-black tracking-wider rounded-lg">{{ $order->nomor_order }}</span>
                    <span class="text-xs font-bold text-gray-400">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</span>
                </div>
                
                <div>
                    <h3 class="text-sm font-black text-gray-500 uppercase tracking-widest mb-1">Pemesan</h3>
                    {{-- Opsional: Jika Anda punya relasi ke merchant, panggil nama kantinnya di sini --}}
                    <p class="text-lg font-bold text-gray-900">ID Merchant: {{ $order->merchant_id }}</p>
                    <p class="text-sm font-medium text-gray-500 mt-1 flex items-start gap-1.5">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Diminta Tgl: <span class="font-bold text-gray-700">{{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</span>
                    </p>
                    @if($order->catatan)
                        <div class="mt-2 inline-flex items-start gap-1.5 bg-orange-50 text-orange-700 px-3 py-2 rounded-lg text-xs font-medium border border-orange-100">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Catatan Merchant: <span class="font-bold">{{ $order->catatan }}</span>
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-sm text-gray-600 leading-relaxed"><span class="text-gray-400 uppercase tracking-widest text-xs font-bold mb-1 block">Rincian Barang:</span> {!! $stringItemDipesan !!}</p>
                </div>
            </div>

            <div class="flex flex-col justify-between items-start md:items-end border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6 min-w-[250px]">
                <div class="text-left md:text-right w-full mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Pencairan Dana</p>
                    <h3 class="text-2xl font-black text-gray-900">Rp {{ number_format($totalNilaiPO, 0, ',', '.') }}</h3>
                </div>

                <div class="w-full flex flex-col gap-2">
                    {{-- STATUS LOGIC PANEL --}}
                    @if($order->status === 'menunggu_lkbb')
                        <div class="w-full bg-amber-50 border border-amber-200 p-3 rounded-xl flex items-start gap-3">
                            <div class="bg-amber-100 p-1.5 rounded-lg text-amber-600 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-amber-800 uppercase tracking-wider">Menunggu Pencairan LKBB</h4>
                                <p class="text-[10px] text-amber-700 mt-0.5 leading-snug">Pesanan ini sedang di-review oleh LKBB. Barang <b>belum perlu</b> disiapkan.</p>
                            </div>
                        </div>

                    @elseif($order->status === 'diproses_pemasok')
                        <div class="w-full bg-emerald-50 border border-emerald-200 p-3 rounded-xl flex items-start gap-3">
                            <div class="bg-emerald-100 p-1.5 rounded-lg text-emerald-600 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-emerald-800 uppercase tracking-wider">Dana Telah Cair</h4>
                                <p class="text-[10px] text-emerald-700 mt-0.5 leading-snug">LKBB telah mentransfer dana ke sistem. Anda sudah bisa <b>menyiapkan pesanan</b> ini.</p>
                            </div>
                        </div>

                    @elseif($order->status === 'ditolak')
                        <div class="w-full bg-rose-50 border border-rose-200 p-3 rounded-xl flex items-start gap-3">
                            <div class="bg-rose-100 p-1.5 rounded-lg text-rose-600 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-rose-800 uppercase tracking-wider">Pendanaan Ditolak</h4>
                                <p class="text-[10px] text-rose-700 mt-0.5 leading-snug">LKBB menolak pengajuan dana pesanan ini. Transaksi dibatalkan.</p>
                            </div>
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
            <h3 class="text-lg font-black text-gray-800">Belum Ada PO Masuk</h3>
            <p class="text-sm font-medium text-gray-500 mt-1 max-w-sm text-center">Pesanan baru dari jaringan kantin/merchant akan muncul di sini.</p>
        </div>
        @endforelse
        
        <div class="mt-6">
            {{ $daftarPesanan->links() }}
        </div>
    </div>

</div>