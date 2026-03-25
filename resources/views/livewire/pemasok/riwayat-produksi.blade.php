<div class="p-6 relative">
    {{-- Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Riwayat Produksi</h1>
            <p class="text-sm text-gray-500">Pantau semua histori aktivitas produksi dapur pusat Anda.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="relative group">
                <input wire:model.live="search" type="text" placeholder="Cari batch atau produk..." 
                       class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-2xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64 transition-all bg-white shadow-sm">
                <div class="absolute left-3 top-3 text-gray-400 group-focus-within:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex justify-between items-center">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Produksi</p>
                <h3 class="text-3xl font-black text-gray-800 mt-1">{{ $stats['total'] }}</h3>
            </div>
            <div class="p-3 bg-orange-50 text-orange-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex justify-between items-center">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest text-green-500">Berhasil</p>
                <h3 class="text-3xl font-black text-gray-800 mt-1">{{ $stats['sukses'] }}</h3>
            </div>
            <div class="p-3 bg-green-50 text-green-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex justify-between items-center">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest text-red-500">Terkendala</p>
                <h3 class="text-3xl font-black text-gray-800 mt-1">{{ $stats['kendala'] }}</h3>
            </div>
            <div class="p-3 bg-red-50 text-red-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Batch</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Produk</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu Produksi</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Penanggung Jawab</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($riwayat as $item)
                    <tr class="hover:bg-gray-50/80 transition-all group">
                        <td class="px-6 py-5">
                            <span class="text-sm font-bold text-gray-900">{{ $item['id'] }}</span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-bold text-gray-800">{{ $item['item'] }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">Jumlah: {{ $item['jumlah'] }} {{ $item['satuan'] }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm text-gray-700">{{ $item['tanggal'] }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $item['waktu'] }} WIB</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-[10px] font-bold">
                                    {{ substr($item['pic'], 0, 1) }}
                                </div>
                                <span class="text-sm text-gray-600 font-medium">{{ $item['pic'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($item['status_color'] == 'green')
                                <span class="px-3 py-1 bg-green-50 text-green-600 text-[11px] font-bold rounded-full border border-green-100 shadow-sm">
                                    {{ $item['status'] }}
                                </span>
                            @else
                                <span class="px-3 py-1 bg-red-50 text-red-600 text-[11px] font-bold rounded-full border border-red-100 shadow-sm">
                                    {{ $item['status'] }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right">
                            <button wire:click="openModal('{{ $item['id'] }}')" class="px-4 py-2 bg-white border border-gray-200 text-orange-600 text-xs font-bold rounded-xl hover:bg-orange-50 hover:border-orange-200 transition-colors shadow-sm">
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center">
                            <p class="text-gray-500 font-medium">Data tidak ditemukan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Detail Produksi Menggunakan Alpine.js untuk transisi (Terhubung dengan Livewire $entangle) --}}
    <div x-data="{ open: @entangle('isModalOpen') }" 
         x-show="open" 
         class="fixed inset-0 z-50 flex items-center justify-center px-4 sm:px-0" 
         style="display: none;">
        
        {{-- Background Overlay --}}
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"
             @click="open = false">
        </div>

        {{-- Modal Panel --}}
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-2xl z-50 relative border border-gray-100">
            
            @if($selectedItem)
            {{-- Header Modal --}}
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Detail Produksi</h3>
                    <p class="text-sm text-gray-500 font-medium mt-0.5">{{ $selectedItem['id'] }}</p>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-full transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-orange-50/50 rounded-2xl border border-orange-100/50">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Item Diproduksi</p>
                        <p class="text-sm font-bold text-gray-900 mt-1">{{ $selectedItem['item'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Hasil</p>
                        <p class="text-sm font-bold text-gray-900 mt-1">{{ $selectedItem['jumlah'] }} {{ $selectedItem['satuan'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Tanggal & Waktu</p>
                        <p class="text-sm font-bold text-gray-900 mt-1">{{ $selectedItem['tanggal'] }} ({{ $selectedItem['waktu'] }})</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Penanggung Jawab</p>
                        <p class="text-sm font-bold text-gray-900 mt-1">{{ $selectedItem['pic'] }}</p>
                    </div>
                </div>

                <h4 class="text-sm font-bold text-gray-800 mb-3 border-b border-gray-100 pb-2">Rincian Bahan Baku Digunakan</h4>
                <div class="space-y-3">
                    @foreach($selectedItem['bahan_baku'] as $bahan)
                    <div class="flex justify-between items-center bg-white border border-gray-100 p-3 rounded-xl shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">{{ $bahan['nama'] }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $bahan['qty'] }} <span class="text-xs text-gray-500 font-medium">{{ $bahan['satuan'] }}</span></span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button @click="open = false" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50 transition-colors">Tutup</button>
                <button class="px-5 py-2.5 bg-orange-600 text-white text-sm font-bold rounded-xl hover:bg-orange-700 shadow-md shadow-orange-200 transition-all">Cetak Laporan</button>
            </div>
            @endif
        </div>
    </div>

</div>