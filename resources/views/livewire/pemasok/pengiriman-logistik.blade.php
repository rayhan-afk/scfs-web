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
    <div class="space-y-3">
        @forelse($orders as $i => $order)

            {{-- ============================================================ --}}
            {{-- VARIAN COMPACT LIST untuk tab Selesai (PO sudah diterima)      --}}
            {{-- ============================================================ --}}
            @if($activeTab === 'selesai')
                <button wire:key="order-{{ $order->id }}" wire:click="bukaModalDetail({{ $order->id }})" type="button"
                        class="scfs-fade-in-up group block w-full text-left relative overflow-hidden rounded-2xl border border-white/60 bg-white/85 backdrop-blur-xl px-4 sm:px-5 py-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:border-emerald-200"
                        style="animation-delay: {{ $i * 40 }}ms;">
                    <div class="flex items-center gap-4">
                        {{-- Status icon --}}
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-lime-500 text-white shadow-md shadow-emerald-200 ring-2 ring-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>

                        {{-- Info --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700">{{ $order->nomor_order }}</span>
                                <span class="text-[10px] font-bold text-gray-400">Diterima {{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y · H:i') }}</span>
                            </div>
                            <p class="mt-1 text-sm font-black leading-tight text-gray-900 truncate">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</p>
                            <p class="text-[11px] font-medium text-gray-500">
                                {{ $order->details->count() }} item · <span class="font-black text-emerald-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</span>
                                @if($order->nama_kurir)
                                    · 🛵 {{ $order->nama_kurir }}
                                @endif
                            </p>
                        </div>

                        {{-- CTA --}}
                        <div class="hidden sm:flex items-center gap-1 text-[11px] font-black text-emerald-600 group-hover:translate-x-0.5 transition">
                            Rincian
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                        <svg class="sm:hidden h-5 w-5 shrink-0 text-gray-300 group-hover:text-emerald-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>

            @else
            {{-- ============================================================ --}}
            {{-- VARIAN FULL CARD untuk tab Perlu Dikirim / Sedang Jalan        --}}
            {{-- ============================================================ --}}
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
            @endif {{-- /varian compact vs full --}}
        @empty
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white/60 py-20 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-amber-100 text-orange-500">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-800">Tidak ada pengiriman</h3>
                <p class="mt-1 text-sm font-bold text-gray-500">Data pesanan di kategori ini masih kosong.</p>
            </div>
        @endforelse

        @if($orders->hasPages())
            <div class="mt-5">{{ $orders->links() }}</div>
        @endif
    </div>

    {{-- MODAL LIHAT DETAIL PESANAN --}}
    <div x-data="{ open: @entangle('showModalDetail') }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-md no-print"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative z-50 flex w-full max-w-2xl max-h-[90vh] flex-col overflow-hidden rounded-[28px] border border-white/60 bg-white shadow-[0_30px_80px_-20px_rgba(234,88,12,0.45)] no-print">

            {{-- Hero header oranye --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-amber-500 to-orange-600 px-6 py-5 shrink-0">
                <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl"></div>
                <div class="pointer-events-none absolute -left-8 -bottom-12 h-32 w-32 rounded-full bg-amber-300/30 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-100/90">Detail Pesanan Merchant</p>
                        <h3 class="mt-1 truncate text-xl font-black tracking-tight text-white">{{ $this->selectedOrder->merchant->merchantProfile->nama_kantin ?? $this->selectedOrder->merchant->name ?? '—' }}</h3>
                        <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg border border-white/30 bg-white/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white backdrop-blur">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            {{ $this->selectedOrder->nomor_order ?? '' }}
                        </span>
                    </div>
                    <button @click="open = false" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/30 bg-white/15 text-white shadow-md transition hover:bg-white/30 active:scale-95">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto bg-gradient-to-b from-orange-50/40 to-white p-6 space-y-5">
                @if($this->selectedOrder)

                {{-- Info penerima + lokasi --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="relative overflow-hidden rounded-2xl border border-orange-100 bg-white p-4 shadow-sm">
                        <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-orange-100/60 blur-2xl"></div>
                        <div class="relative">
                            <p class="text-[10px] font-black uppercase tracking-widest text-orange-600">Penerima</p>
                            <p class="mt-1 text-sm font-black leading-tight text-gray-900">{{ $this->selectedOrder->merchant->merchantProfile->nama_pemilik ?? $this->selectedOrder->merchant->name ?? '-' }}</p>
                            <p class="mt-1 text-[11px] font-bold text-gray-500">{{ $this->selectedOrder->merchant->merchantProfile->no_hp ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="relative overflow-hidden rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
                        <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-amber-100/60 blur-2xl"></div>
                        <div class="relative">
                            <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">Lokasi</p>
                            <p class="mt-1 text-sm font-black leading-tight text-gray-900">Blok {{ $this->selectedOrder->merchant->merchantProfile->lokasi_blok ?? '—' }}</p>
                            <p class="mt-1 text-[11px] font-bold text-gray-500">Butuh {{ \Carbon\Carbon::parse($this->selectedOrder->tanggal_kebutuhan)->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Catatan merchant --}}
                @if($this->selectedOrder->catatan)
                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2.5 text-[11px] font-bold text-amber-800">
                        ✱ Catatan merchant: {{ $this->selectedOrder->catatan }}
                    </div>
                @endif

                {{-- Kurir card (kalau sudah diatur) --}}
                @if($this->selectedOrder->nama_kurir)
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Armada Kurir</p>
                        <x-tracking.courier-card
                            :name="$this->selectedOrder->nama_kurir"
                            :phone="$this->selectedOrder->no_hp_kurir"
                            :resi="$this->selectedOrder->no_resi"
                            :status="$this->selectedOrder->status"
                            variant="pemasok"
                        />
                    </div>
                @endif

                {{-- Progress + timeline --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <x-tracking.progress-track :percentage="$this->selectedOrderProgress" variant="pemasok" :label="'Progres Pengiriman'" :sublabel="$this->selectedOrderProgress . '%'" />
                    <div class="mt-4 rounded-xl bg-gray-50/60 p-3">
                        <x-tracking.status-timeline :events="$this->selectedOrderEvents" variant="pemasok" />
                    </div>
                </div>

                {{-- Rincian barang --}}
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Rincian Barang ({{ $this->selectedOrder->details->count() }} item)</p>
                    <div class="overflow-hidden rounded-2xl border border-orange-100 bg-white shadow-sm">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gradient-to-r from-orange-50 to-amber-50 text-[10px] uppercase">
                                <tr>
                                    <th class="px-4 py-3 font-black tracking-wider text-orange-700">Produk</th>
                                    <th class="px-4 py-3 text-center font-black tracking-wider text-orange-700">Qty</th>
                                    <th class="px-4 py-3 text-right font-black tracking-wider text-orange-700">Harga</th>
                                    <th class="px-4 py-3 text-right font-black tracking-wider text-orange-700">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-orange-50">
                                @foreach($this->selectedOrder->details as $item)
                                <tr class="transition hover:bg-orange-50/40">
                                    <td class="px-4 py-3 font-bold text-gray-800">{{ $item->nama_produk_snapshot }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex h-7 min-w-[28px] items-center justify-center rounded-lg bg-orange-100 px-2 text-[11px] font-black text-orange-700">{{ $item->qty }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs font-bold text-gray-600">Rp {{ number_format(($item->harga_modal_snapshot + $item->margin_pemasok_snapshot), 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-black text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gradient-to-r from-orange-500 to-amber-500 text-white">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-[11px] font-black uppercase tracking-widest">Total Pendapatan</td>
                                    <td class="px-4 py-3 text-right text-base font-black">Rp {{ number_format($this->selectedOrder->total_estimasi, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 bg-white/80 px-6 py-4 backdrop-blur">
                <span class="text-[10px] font-bold text-gray-400">PO {{ $this->selectedOrder->nomor_order ?? '' }}</span>
                <button @click="open = false" class="rounded-xl bg-gradient-to-r from-gray-800 to-gray-900 px-5 py-2.5 text-sm font-black text-white shadow-md transition hover:from-gray-900 hover:to-black active:scale-[0.98]">Tutup</button>
            </div>
        </div>
    </div>

    {{-- MODAL ATUR PENGIRIMAN --}}
    <div x-data="{ open: @entangle('showModalAtur') }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-md no-print"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative z-50 w-full max-w-lg overflow-hidden rounded-[28px] border border-white/60 bg-white shadow-[0_30px_80px_-20px_rgba(234,88,12,0.45)] no-print">

            {{-- Hero header oranye --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-amber-500 to-orange-600 px-6 py-5">
                <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl"></div>
                <div class="pointer-events-none absolute -left-8 -bottom-12 h-32 w-32 rounded-full bg-amber-300/30 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-100/90">Atur Pengiriman</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight text-white">🛵 Kirim Barang</h3>
                        <p class="mt-0.5 text-[11px] font-medium text-amber-50/90">Tetapkan kurir & resi sebelum diberangkatkan.</p>
                    </div>
                    <button @click="open = false" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/30 bg-white/15 text-white shadow-md transition hover:bg-white/30 active:scale-95">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            @if($this->selectedOrder)
            <form wire:submit.prevent="simpanPengiriman" class="bg-gradient-to-b from-orange-50/40 to-white px-6 py-5 space-y-4">

                {{-- Tujuan pengiriman --}}
                <div class="relative overflow-hidden rounded-2xl border border-orange-100 bg-white p-4 shadow-sm">
                    <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-orange-100/60 blur-2xl"></div>
                    <div class="relative flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-md shadow-orange-200 ring-2 ring-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-widest text-orange-600">Tujuan Pengiriman</p>
                            <p class="mt-0.5 text-sm font-black leading-tight text-gray-900 truncate">{{ $this->selectedOrder->merchant->merchantProfile->nama_kantin ?? $this->selectedOrder->merchant->name }}</p>
                            <p class="mt-0.5 text-[11px] font-bold text-gray-500">Blok {{ $this->selectedOrder->merchant->merchantProfile->lokasi_blok ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Field: Nama Kurir --}}
                <div>
                    <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-500">Nama Kurir</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <input type="text" wire:model="nama_kurir"
                               placeholder="cth: Budi Santoso"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-200">
                    </div>
                    @error('nama_kurir') <span class="mt-1 inline-block text-[11px] font-bold text-rose-500">⚠ {{ $message }}</span> @enderror
                </div>

                {{-- Field: No HP Kurir --}}
                <div>
                    <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-500">No HP Kurir</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <input type="tel" wire:model="no_hp_kurir"
                               placeholder="cth: 081234567890"
                               inputmode="numeric"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-200">
                    </div>
                    @error('no_hp_kurir') <span class="mt-1 inline-block text-[11px] font-bold text-rose-500">⚠ {{ $message }}</span> @enderror
                </div>

                {{-- Field: Nomor Resi --}}
                <div>
                    <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-500">Nomor Resi / Surat Jalan</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <input type="text" wire:model="no_resi"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-2.5 text-sm font-mono font-bold uppercase tracking-wider text-gray-700 shadow-sm outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-200">
                    </div>
                    @error('no_resi') <span class="mt-1 inline-block text-[11px] font-bold text-rose-500">⚠ {{ $message }}</span> @enderror
                </div>

                {{-- Action buttons --}}
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="open = false" class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-600 transition hover:bg-gray-50 active:scale-[0.98]">Batal</button>
                    <button type="submit"
                            wire:loading.attr="disabled" wire:target="simpanPengiriman"
                            class="flex-1 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-200 transition hover:shadow-xl hover:shadow-orange-300 active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <svg wire:loading.remove wire:target="simpanPengiriman" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <svg wire:loading wire:target="simpanPengiriman" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span wire:loading.remove wire:target="simpanPengiriman">Tandai Dikirim</span>
                        <span wire:loading wire:target="simpanPengiriman">Menyimpan…</span>
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- MODAL CETAK SURAT JALAN --}}
    <div x-data="{ open: @entangle('showModalCetak') }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-md no-print"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative z-50 flex w-full max-w-lg max-h-[90vh] flex-col overflow-hidden rounded-[28px] border border-white/60 bg-white shadow-[0_30px_80px_-20px_rgba(234,88,12,0.45)]">

            {{-- Hero header oranye (tidak ikut tercetak) --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-amber-500 to-orange-600 px-6 py-5 no-print shrink-0">
                <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl"></div>
                <div class="pointer-events-none absolute -left-8 -bottom-12 h-32 w-32 rounded-full bg-amber-300/30 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-100/90">Cetak Dokumen</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight text-white">📄 Surat Jalan</h3>
                        <p class="mt-0.5 text-[11px] font-medium text-amber-50/90">Preview dokumen pengiriman sebelum dicetak.</p>
                    </div>
                    <button @click="open = false" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/30 bg-white/15 text-white shadow-md transition hover:bg-white/30 active:scale-95 no-print">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto bg-gradient-to-b from-orange-50/30 to-white p-5 no-print">

            <div id="area-cetak-label" class="p-6 bg-white text-black rounded-2xl border border-gray-200 shadow-sm">
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

            </div> {{-- /scroll wrapper preview --}}

            {{-- Footer aksi --}}
            <div class="flex shrink-0 gap-3 border-t border-gray-100 bg-white/80 px-5 py-4 backdrop-blur no-print">
                <button @click="open = false" class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-50 active:scale-[0.98]">Tutup</button>
                <button onclick="window.print()" class="flex-1 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-orange-200 transition hover:shadow-xl hover:shadow-orange-300 active:scale-[0.98] flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Surat Jalan
                </button>
            </div>
        </div>
    </div>

</div>