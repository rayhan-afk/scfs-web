<div class="p-6 relative">
    
    {{-- CSS Khusus untuk Fitur Cetak --}}
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
            <p class="text-sm text-gray-500 mt-1">Atur armada, cetak surat jalan, dan lacak status pengiriman.</p>
        </div>
        
        <div class="relative group">
            <input wire:model.live="search" type="text" placeholder="Cari Resi atau Merchant..." 
                   class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-2xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-full sm:w-72 transition-all bg-white shadow-sm">
            <div class="absolute left-3 top-3 text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Tabs Ala Shopee --}}
    <div class="flex overflow-x-auto space-x-2 bg-white p-1.5 rounded-2xl w-full sm:w-max mb-6 border border-gray-100 shadow-sm hide-scrollbar">
        <button wire:click="setTab('perlu_dikirim')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'perlu_dikirim' ? 'bg-orange-50 text-orange-600' : 'text-gray-500 hover:bg-gray-50' }}">
            Perlu Dikirim <span class="ml-1 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full">1</span>
        </button>
        <button wire:click="setTab('sedang_dikirim')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'sedang_dikirim' ? 'bg-orange-50 text-orange-600' : 'text-gray-500 hover:bg-gray-50' }}">
            Sedang Dikirim
        </button>
        <button wire:click="setTab('riwayat')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'riwayat' ? 'bg-orange-50 text-orange-600' : 'text-gray-500 hover:bg-gray-50' }}">
            Riwayat (Selesai/Batal)
        </button>
    </div>

    {{-- List Pengiriman --}}
    <div class="space-y-4">
        @forelse($pengiriman as $item)
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col md:flex-row gap-6">
            
            {{-- Info Sebelah Kiri --}}
            <div class="flex-1 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-black tracking-wider rounded-lg">{{ $item['id'] }}</span>
                        @if($activeTab === 'sedang_dikirim')
                            <span class="text-xs font-bold text-orange-500">Resi: {{ $item['no_resi'] }}</span>
                        @endif
                    </div>
                    @if($activeTab === 'riwayat')
                        <span class="text-xs font-bold text-green-500 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Pesanan Selesai</span>
                    @endif
                </div>
                
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-800">{{ $item['pembeli'] }}</h3>
                        <p class="text-sm font-medium text-gray-500 mt-1">{{ $item['alamat'] }}</p>
                        <p class="text-xs font-bold text-gray-400 mt-2 bg-gray-50 p-2 rounded-lg inline-block">📦 {{ $item['item'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Kanan --}}
            <div class="flex flex-col justify-end items-end gap-3 md:w-64 border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6">
                @if($activeTab === 'perlu_dikirim')
                    <button wire:click="bukaModalAtur('{{ $item['id'] }}')" class="w-full bg-orange-600 text-white font-bold py-2.5 rounded-xl hover:bg-orange-700 shadow-md shadow-orange-200 transition-all text-sm">
                        Atur Pengiriman
                    </button>
                    {{-- DIUPDATE: Tambah wire:click cetakLabel --}}
                    <button wire:click="cetakLabel('{{ $item['id'] }}')" class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all text-sm flex justify-center items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak Label Pengiriman
                    </button>
                @elseif($activeTab === 'sedang_dikirim')
                    <div class="w-full text-right mb-2">
                        <p class="text-xs text-gray-400 font-bold uppercase">Kurir</p>
                        <p class="text-sm font-bold text-gray-800">{{ $item['kurir'] }}</p>
                    </div>
                    <button wire:click="bukaModalLacak('{{ $item['id'] }}')" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-200 transition-all text-sm">
                        Lacak Status
                    </button>
                    {{-- DIUPDATE: Tambah wire:click bukaModalUpdate --}}
                    <button wire:click="bukaModalUpdate('{{ $item['id'] }}')" class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all text-sm flex justify-center items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Update Tracking
                    </button>
                @else
                    <div class="w-full text-right mb-2">
                        <p class="text-xs text-gray-400 font-bold uppercase">Tiba Pada</p>
                        <p class="text-sm font-bold text-green-600">{{ $item['waktu_sampai'] }}</p>
                    </div>
                    <button wire:click="bukaModalLacak('{{ $item['id'] }}')" class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all text-sm">
                        Lihat Bukti Kirim
                    </button>
                @endif
            </div>

        </div>
        @empty
        <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            </div>
            <h3 class="text-lg font-black text-gray-800">Kosong</h3>
            <p class="text-sm font-bold text-gray-500 mt-1">Tidak ada pengiriman di kategori ini.</p>
        </div>
        @endforelse
    </div>

    {{-- MODAL ATUR PENGIRIMAN --}}
    <div x-data="{ open: @entangle('showModalAtur') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm no-print"></div>
        <div x-show="open" class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-lg z-50 relative no-print">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-black text-gray-800">Atur Pengiriman</h3>
                <button @click="open = false" class="text-gray-400 hover:text-red-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            @if($selectedPesanan)
            <form wire:submit.prevent="simpanPengiriman" class="p-6 space-y-4">
                <div class="p-4 bg-orange-50 rounded-xl border border-orange-100 mb-4">
                    <p class="text-xs font-bold text-orange-600 uppercase">Tujuan</p>
                    <p class="text-sm font-black text-gray-900 mt-1">{{ $selectedPesanan['pembeli'] }}</p>
                    <p class="text-xs text-gray-600 mt-0.5">{{ $selectedPesanan['alamat'] }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Ekspedisi / Kurir</label>
                    <select wire:model="kurir" class="w-full rounded-xl border-gray-200 text-sm focus:ring-orange-500 bg-white">
                        <option value="">-- Pilih Kurir --</option>
                        <option value="internal">Kurir Internal SCFS</option>
                        <option value="lalamove">Lalamove</option>
                        <option value="grab">Grab Express</option>
                        <option value="gosend">GoSend</option>
                    </select>
                    @error('kurir') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Resi / Surat Jalan</label>
                    <input type="text" wire:model="no_resi" class="w-full rounded-xl border-gray-200 text-sm focus:ring-orange-500 font-bold bg-gray-50">
                    @error('no_resi') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="pt-4 flex gap-3">
                    <button type="button" @click="open = false" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl text-sm hover:bg-gray-200">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-orange-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-orange-700">Cetak & Kirim</button>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- MODAL LACAK PENGIRIMAN (ALA SHOPEE) --}}
    <div x-data="{ open: @entangle('showModalLacak') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm no-print"></div>
        <div x-show="open" class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-md z-50 relative flex flex-col max-h-[90vh] no-print">
            <div class="p-6 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-black text-gray-800">Status Pengiriman</h3>
                        <p class="text-sm font-bold text-orange-600 mt-1">Resi: {{ $selectedPesanan['no_resi'] ?? '-' }}</p>
                    </div>
                    <button @click="open = false" class="text-gray-400 hover:text-red-500 bg-gray-200 rounded-full p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto">
                @if($selectedPesanan && isset($selectedPesanan['pelacakan']))
                    <div class="relative border-l-2 border-gray-100 ml-3 space-y-8">
                        @foreach($selectedPesanan['pelacakan'] as $track)
                            <div class="relative pl-6">
                                {{-- Bulatan Timeline --}}
                                @if($track['aktif'])
                                    <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-orange-500 ring-4 ring-orange-100"></span>
                                @else
                                    <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full bg-gray-300"></span>
                                @endif
                                
                                {{-- Teks Timeline --}}
                                <div>
                                    <p class="text-sm {{ $track['aktif'] ? 'font-black text-orange-600' : 'font-bold text-gray-600' }}">
                                        {{ $track['status'] }}
                                    </p>
                                    <p class="text-xs font-medium text-gray-400 mt-1">{{ $track['waktu'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <div class="p-4 border-t border-gray-100 bg-white flex-shrink-0">
                <button @click="open = false" class="w-full py-3 bg-gray-100 text-gray-700 font-bold rounded-xl text-sm hover:bg-gray-200 transition-colors">Tutup Pelacakan</button>
            </div>
        </div>
    </div>

    {{-- MODAL UPDATE TRACKING (BARU) --}}
    <div x-data="{ open: @entangle('showModalUpdate') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm no-print"></div>
        <div x-show="open" class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-md z-50 relative no-print">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-black text-gray-800">Update Status Resi</h3>
                <button @click="open = false" class="text-gray-400 hover:text-red-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            @if($selectedPesanan)
            <form wire:submit.prevent="simpanTracking" class="p-6 space-y-4">
                <p class="text-sm text-gray-600">Masukkan update lokasi atau status terbaru untuk pesanan <strong class="text-gray-900">{{ $selectedPesanan['id'] }}</strong>.</p>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Status Saat Ini</label>
                    <textarea wire:model="newTrackingStatus" rows="3" placeholder="Contoh: Kurir telah sampai di kota tujuan..." class="w-full rounded-xl border-gray-200 text-sm focus:ring-orange-500 resize-none"></textarea>
                    @error('newTrackingStatus') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="w-full py-3 bg-orange-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-orange-700">Simpan Update</button>
            </form>
            @endif
        </div>
    </div>

    {{-- MODAL CETAK LABEL / PRINT PREVIEW (BARU) --}}
    <div x-data="{ open: @entangle('showModalCetak') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div x-show="open" @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm no-print"></div>
        <div x-show="open" class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-lg z-50 relative">
            
            {{-- AREA YANG AKAN DICETAK --}}
            <div id="area-cetak-label" class="p-8 bg-white text-black">
                @if($selectedPesanan)
                <div class="border-2 border-black p-4 rounded-lg relative">
                    <div class="text-center border-b-2 border-black pb-4 mb-4">
                        <h2 class="text-2xl font-black uppercase tracking-widest">SCFS LOGISTIK</h2>
                        <p class="text-sm font-bold">Resi: {{ $selectedPesanan['no_resi'] ?? 'DRAFT' }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs uppercase text-gray-500 mb-1">Pengirim:</p>
                            <p class="font-bold text-sm">Pemasok Pusat SCFS</p>
                            <p class="text-xs">Gudang Utama Bandung</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500 mb-1">Penerima:</p>
                            <p class="font-bold text-sm">{{ $selectedPesanan['pembeli'] }}</p>
                            <p class="text-xs">{{ $selectedPesanan['alamat'] }}</p>
                        </div>
                    </div>

                    <div class="border-t-2 border-black pt-4">
                        <p class="text-xs uppercase text-gray-500 mb-1">Isi Paket:</p>
                        <p class="text-sm font-bold">{{ $selectedPesanan['item'] }}</p>
                        <p class="text-xs mt-2">Order ID: {{ $selectedPesanan['id'] }}</p>
                    </div>

                    {{-- Barcode Dummy --}}
                    <div class="mt-6 text-center">
                        <svg class="h-16 w-full opacity-70" preserveAspectRatio="none" viewBox="0 0 100 20">
                            <path stroke="black" stroke-width="2" d="M10,0 v20 M14,0 v20 M16,0 v20 M20,0 v20 M24,0 v20 M26,0 v20 M30,0 v20 M34,0 v20 M38,0 v20 M42,0 v20 M44,0 v20 M50,0 v20 M56,0 v20 M60,0 v20 M64,0 v20 M68,0 v20 M72,0 v20 M76,0 v20 M80,0 v20 M84,0 v20 M86,0 v20 M90,0 v20"/>
                        </svg>
                        <p class="text-[10px] mt-1 tracking-widest">{{ $selectedPesanan['no_resi'] ?? 'DRAFT' }}</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- TOMBOL AKSI CETAK (Dihilangkan saat di-print via class no-print) --}}
            <div class="p-4 bg-gray-50 border-t border-gray-100 flex gap-3 no-print">
                <button @click="open = false" class="flex-1 px-4 py-2.5 bg-white border border-gray-200 text-gray-600 font-bold rounded-xl text-sm hover:bg-gray-100">Batal</button>
                <button onclick="window.print()" class="flex-1 px-4 py-2.5 bg-orange-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-orange-700 flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Sekarang
                </button>
            </div>
        </div>
    </div>

</div>