<div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-6 lg:py-8">

    {{-- CSS Khusus Cetak (jangan dihapus, dipakai oleh modal surat jalan) --}}
    <style>
        @media print {
            body * { visibility: hidden; }
            #area-cetak-label, #area-cetak-label * { visibility: visible; }
            #area-cetak-label { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>

    {{-- ===== Hero header dengan gradien oranye ===== --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-orange-500 via-amber-500 to-orange-600 p-6 sm:p-8 mb-6 shadow-[0_20px_50px_-20px_rgba(234,88,12,0.5)]">
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-10 -bottom-20 h-56 w-56 rounded-full bg-amber-300/30 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="text-white">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-100/90">Pemasok · Operasional Armada</p>
                <h1 class="mt-1 text-2xl sm:text-3xl font-black tracking-tight">Halo, {{ Auth::user()->name }} 👋</h1>
                <p class="mt-1 text-sm text-amber-50/90 font-medium">Atur armada, lacak setiap paket, dan pastikan barang tiba di kantin merchant tepat waktu.</p>
            </div>
            <div class="relative w-full md:w-80">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nomor PO / kantin / resi…"
                       class="w-full rounded-2xl border border-white/40 bg-white/95 backdrop-blur pl-11 pr-4 py-3 text-sm font-bold text-gray-700 placeholder:font-medium placeholder:text-gray-400 shadow-lg shadow-orange-900/20 outline-none focus:ring-2 focus:ring-white">
                <svg class="absolute left-3.5 top-3.5 h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- ===== Stats row ===== --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <x-tracking.stat-tile
            label="Perlu Dikirim"
            :value="$this->stats['perlu_dikirim']"
            caption="armada belum diatur"
            accent="orange"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Sedang Jalan"
            :value="$this->stats['sedang_jalan']"
            caption="armada aktif"
            accent="amber"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 4m0 0v3a1 1 0 01-1 1h-2M14 8h4l3 4\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Selesai Bulan Ini"
            :value="$this->stats['selesai_bulan_ini']"
            caption="diterima merchant"
            accent="emerald"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2.5\' d=\'M5 13l4 4L19 7\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Nilai Aktif"
            :value="'Rp ' . number_format($this->stats['nilai_aktif'], 0, ',', '.')"
            caption="dalam pengiriman"
            accent="sky"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1\'/></svg>'"
        />
    </div>

    {{-- ===== Flash message ===== --}}
    @if (session()->has('message'))
        <div class="mb-5 flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50/80 backdrop-blur px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm scfs-fade-in-up">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- ===== Tabs (orange-themed) ===== --}}
    <div class="mb-6 flex w-full overflow-x-auto rounded-2xl border border-white/60 bg-white/80 p-1.5 shadow-sm backdrop-blur-xl sm:w-max scrollbar-hide">
        <button wire:click="setTab('diproses_pemasok')" @class([
            'relative flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md shadow-orange-200' => $activeTab === 'diproses_pemasok',
            'text-gray-500 hover:bg-gray-50' => $activeTab !== 'diproses_pemasok',
        ])>
            Perlu Dikirim
            @if($countPerluDikirim > 0)
                <span @class([
                    'ml-1.5 rounded-full px-2 py-0.5 text-[10px] font-black',
                    'bg-white/25 text-white' => $activeTab === 'diproses_pemasok',
                    'bg-orange-100 text-orange-600' => $activeTab !== 'diproses_pemasok',
                ])>{{ $countPerluDikirim }}</span>
            @endif
        </button>
        <button wire:click="setTab('dikirim')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md shadow-amber-200' => $activeTab === 'dikirim',
            'text-gray-500 hover:bg-gray-50' => $activeTab !== 'dikirim',
        ])>Sedang Jalan</button>
        <button wire:click="setTab('selesai')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-emerald-500 to-lime-500 text-white shadow-md shadow-emerald-200' => $activeTab === 'selesai',
            'text-gray-500 hover:bg-gray-50' => $activeTab !== 'selesai',
        ])>Diterima Merchant</button>
    </div>

    {{-- ===== Order list ===== --}}
    <div class="space-y-4">
        @forelse($orders as $i => $order)
            @php $tracking = $trackingByOrder[$order->id] ?? ['events' => collect(), 'progress' => 0]; @endphp
            <article wire:key="order-{{ $order->id }}" class="scfs-fade-in-up relative overflow-hidden rounded-3xl border border-white/60 bg-white/85 backdrop-blur-xl p-5 sm:p-6 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)]" style="animation-delay: {{ $i * 60 }}ms;">

                {{-- Header card --}}
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 mb-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-orange-700">{{ $order->nomor_order }}</span>
                        <span class="text-xs font-bold text-gray-400">Butuh tgl {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Nilai</p>
                        <p class="text-base font-black text-orange-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>
                </header>

                <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                    {{-- Kolom kiri: penerima + timeline + courier --}}
                    <div class="space-y-5">
                        {{-- Penerima --}}
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-md shadow-orange-200 ring-2 ring-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Tujuan</p>
                                <h3 class="text-base font-black leading-tight text-gray-900 truncate">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</h3>
                                <p class="mt-0.5 text-xs font-medium text-gray-600">
                                    Blok <span class="font-bold">{{ $order->merchant->merchantProfile->lokasi_blok ?? 'Belum diatur' }}</span>
                                    · {{ $order->merchant->merchantProfile->nama_pemilik ?? '-' }}
                                </p>
                            </div>
                        </div>

                        {{-- Progress + mini timeline --}}
                        <div>
                            <x-tracking.progress-track :percentage="$tracking['progress']" variant="pemasok" :label="'Progres Pengiriman'" :sublabel="$tracking['progress'] . '%'" />
                            <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                                <x-tracking.status-timeline :events="$tracking['events']" variant="pemasok" />
                            </div>
                        </div>

                        {{-- Catatan merchant --}}
                        @if($order->catatan)
                            <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-[11px] font-bold text-amber-800">
                                ✱ Catatan merchant: {{ $order->catatan }}
                            </div>
                        @endif
                    </div>

                    {{-- Kolom kanan: kurir card + aksi --}}
                    <aside class="flex flex-col gap-3 border-t border-gray-100 pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        @if($order->nama_kurir)
                            <x-tracking.courier-card
                                :name="$order->nama_kurir"
                                :phone="$order->no_hp_kurir"
                                :resi="$order->no_resi"
                                :status="$order->status"
                                variant="pemasok"
                            />
                        @endif

                        @if($activeTab === 'diproses_pemasok')
                            <button wire:click="bukaModalAtur({{ $order->id }})" class="w-full rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-200 transition hover:shadow-xl hover:shadow-orange-300 active:scale-[0.98]">
                                🛵 Atur Kurir & Kirim
                            </button>
                            <div class="flex gap-2">
                                <button wire:click="bukaModalDetail({{ $order->id }})" class="flex-1 rounded-2xl border border-orange-200 bg-white px-3 py-2.5 text-xs font-bold text-orange-600 transition hover:bg-orange-50">Detail</button>
                                <button wire:click="cetakLabel({{ $order->id }})" class="flex-1 rounded-2xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50">Cetak Label</button>
                            </div>
                        @elseif($activeTab === 'dikirim')
                            <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50">Lihat Detail Pesanan</button>
                            <button wire:click="cetakLabel({{ $order->id }})" class="w-full rounded-2xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-xs font-bold text-orange-600 transition hover:bg-orange-100">Cetak Ulang Surat Jalan</button>
                        @else
                            <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-lime-50 border border-emerald-200 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">✓ Diterima merchant</p>
                                <p class="mt-0.5 text-[10px] font-bold text-emerald-600">{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y · H:i') }}</p>
                            </div>
                            <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50">Cek Rincian</button>
                        @endif
                    </aside>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white/60 py-20 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-amber-100 text-orange-500">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-800">Tidak ada pengiriman</h3>
                <p class="mt-1 text-sm font-bold text-gray-500">Data pesanan di kategori ini masih kosong.</p>
            </div>
        @endforelse

        <div class="mt-5">{{ $orders->links() }}</div>
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
                        <p class="text-[10px] uppercase tracking-widest font-bold text-gray-700 mb-2">Diantar Oleh</p>
                        <div class="border border-black/80 rounded-lg p-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border-2 border-black bg-white">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="5.5" cy="17.5" r="2.5"/>
                                        <circle cx="18.5" cy="17.5" r="2.5"/>
                                        <path d="M8 17.5h8"/>
                                        <path d="M13 17.5V8h4l2 4"/>
                                        <path d="M5.5 15 7 9h4l1.5 6"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-black text-sm leading-tight">{{ $this->selectedOrder->nama_kurir ?? '-' }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[10px] font-medium">
                                        <span>
                                            <span class="text-gray-600">HP:</span>
                                            <span class="font-bold">{{ $this->selectedOrder->no_hp_kurir ?? '-' }}</span>
                                        </span>
                                        <span class="hidden sm:inline-block h-0.5 w-0.5 rounded-full bg-black/40"></span>
                                        <span>
                                            <span class="text-gray-600">Resi:</span>
                                            <span class="font-bold uppercase tracking-wider">{{ $this->selectedOrder->no_resi ?? '-' }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
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