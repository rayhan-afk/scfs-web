<div class="p-6 relative pb-32"> {{-- Padding bottom ditambah agar tidak tertutup floating bar --}}
    
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Pembiayaan & Produksi</h1>
        <p class="text-sm text-gray-500 mt-1">Ajukan pencairan dana ke LKBB lalu proses pesanan fisik ke Merchant.</p>
    </div>

    {{-- Notifikasi --}}
    @if (session()->has('message'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Widget Plafon / Limit LKBB --}}
    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-3xl p-6 text-white shadow-lg mb-8 relative overflow-hidden">
        <svg class="absolute right-0 top-0 w-64 h-64 text-white opacity-10 transform translate-x-16 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path></svg>

        <div class="relative z-10 flex flex-col md:flex-row gap-6 justify-between items-center">
            <div class="w-full md:w-1/2">
                <p class="text-emerald-100 font-medium text-sm mb-1 uppercase tracking-wider">Tersedia untuk Dicairkan (Sisa Limit)</p>
                <h2 class="text-4xl font-black mb-4 transition-all">Rp {{ number_format($sisaPlafon, 0, ',', '.') }}</h2>
                
                <div class="w-full bg-emerald-800/50 rounded-full h-2.5 mb-2 overflow-hidden">
                    <div class="bg-white h-2.5 rounded-full transition-all duration-500 ease-in-out" style="width: {{ $persentaseTerpakai }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-emerald-100 font-medium">
                    <span>Terpakai: Rp {{ number_format($plafonTerpakai, 0, ',', '.') }}</span>
                    <span>Total Plafon: Rp {{ number_format($plafonTotal, 0, ',', '.') }}</span>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-white/20 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-100 uppercase font-bold">Mitra Finansial</p>
                        <p class="font-black text-lg">Bank Mandiri (KUM)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Status --}}
    <div class="flex overflow-x-auto space-x-2 bg-white p-1.5 rounded-2xl w-full mb-6 border border-gray-100 shadow-sm hide-scrollbar">
        @foreach(['siap_diajukan' => '1. Siap Diajukan', 'menunggu_lkbb' => '2. Menunggu LKBB', 'dicairkan' => '3. Dana Cair', 'sedang_diproduksi' => '4. Sedang Produksi', 'siap_dikirim' => '5. Siap Kirim'] as $key => $label)
            <button wire:click="setTab('{{ $key }}')" class="flex-none px-5 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === $key ? 'bg-teal-50 text-teal-700' : 'text-gray-500 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Daftar Pesanan --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-12">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        @if($activeTab === 'siap_diajukan') <th class="p-4 w-12 text-center">Pilih</th> @endif
                        <th class="p-4 font-bold">ID Pesanan (PO)</th>
                        <th class="p-4 font-bold">Merchant / Toko</th>
                        <th class="p-4 font-bold">Tanggal</th>
                        <th class="p-4 font-bold text-right">Nominal</th>
                        @if(in_array($activeTab, ['menunggu_lkbb', 'dicairkan', 'sedang_diproduksi'])) <th class="p-4 font-bold text-center">ID Pengajuan LKBB</th> @endif
                        @if(in_array($activeTab, ['dicairkan', 'sedang_diproduksi', 'siap_dikirim'])) <th class="p-4 font-bold text-center">Tindakan</th> @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pesananDitampilkan as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            @if($activeTab === 'siap_diajukan')
                                <td class="p-4 text-center">
                                    <input type="checkbox" wire:model.live="selectedPesanan" value="{{ $item['id'] }}" class="w-5 h-5 text-teal-600 rounded cursor-pointer">
                                </td>
                            @endif
                            <td class="p-4 font-black text-gray-800 text-sm">{{ $item['id'] }}</td>
                            <td class="p-4 text-sm font-medium text-gray-600">{{ $item['merchant'] }}</td>
                            <td class="p-4 text-sm text-gray-500">{{ $item['tanggal'] }}</td>
                            <td class="p-4 font-black text-gray-900 text-right">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                            
                            @if(in_array($activeTab, ['menunggu_lkbb', 'dicairkan', 'sedang_diproduksi']))
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                        {{ $item['id_pengajuan'] }}
                                    </span>
                                </td>
                            @endif

                            @if(in_array($activeTab, ['dicairkan', 'sedang_diproduksi', 'siap_dikirim']))
                                <td class="p-4 text-center">
                                    @if($activeTab === 'dicairkan')
                                        <button wire:click="mulaiProduksi('{{ $item['id'] }}')" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow hover:bg-blue-700 transition">Mulai Produksi</button>
                                    @elseif($activeTab === 'sedang_diproduksi')
                                        <button wire:click="selesaiQC('{{ $item['id'] }}')" class="px-4 py-2 bg-purple-600 text-white text-xs font-bold rounded-xl shadow hover:bg-purple-700 transition">Lolos QC</button>
                                    @elseif($activeTab === 'siap_dikirim')
                                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Menunggu Kurir</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-10 text-center text-gray-400 font-medium">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- FLOATING ACTION BAR --}}
    @if(count($selectedPesanan) > 0 && $activeTab === 'siap_diajukan')
        <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 w-[90%] md:w-auto bg-gray-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex flex-col md:flex-row items-center justify-between gap-8 z-40 border border-gray-700">
            <div class="flex items-center gap-6">
                <div>
                    <span class="block text-xs text-gray-400 font-bold uppercase">Pesanan</span>
                    <span class="block text-lg font-black text-teal-400">{{ count($selectedPesanan) }} PO</span>
                </div>
                <div class="h-8 w-px bg-gray-700"></div>
                <div>
                    <span class="block text-xs text-gray-400 font-bold uppercase">Total Pengajuan</span>
                    <span class="block text-xl font-black">Rp {{ number_format($this->totalPengajuan, 0, ',', '.') }}</span>
                </div>
            </div>
            <button wire:click="$set('showModalKonfirmasi', true)" class="px-8 py-3 bg-teal-600 text-white font-black rounded-2xl shadow-lg hover:bg-teal-700 transition-all flex items-center gap-2">
                Ajukan ke LKBB
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
            </button>
        </div>
    @endif

    {{-- MODAL KONFIRMASI (Bungkus Utama yang diperbaiki) --}}
    <div x-data="{ open: @entangle('showModalKonfirmasi') }" 
         x-show="open" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4" 
         x-cloak>
        
        {{-- Overlay/Backdrop --}}
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

        {{-- Isi Modal --}}
        <div x-show="open" x-transition.scale class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden z-[70] relative">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="text-xl font-black text-gray-800">Konfirmasi Pengajuan</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 space-y-6">
                {{-- Bagian Rekening --}}
                <div class="space-y-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Rekening Tujuan Pencairan</label>
                    <div class="bg-teal-50 border border-teal-100 p-4 rounded-2xl flex items-center gap-4">
                        <div class="bg-teal-600 p-2.5 rounded-xl">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <div class="flex-1 text-sm">
                            <p class="font-black text-teal-900 leading-tight">
                                {{ $pemasokProfile->nama_bank ?? '' }} - {{ $pemasokProfile->nomor_rekening ?? 'Rekening belum diatur' }}
                            </p>
                            <p class="text-[11px] text-teal-600 mt-1 font-medium italic">*Dana ditransfer langsung oleh LKBB.</p>
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="bg-amber-50 p-4 rounded-2xl border border-amber-100 text-[11px] text-amber-700">
                    <p><strong>Catatan:</strong> Tidak ada beban tenor bagi Pemasok. Pelunasan merupakan tanggung jawab Merchant ke LKBB.</p>
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex gap-3">
                    <button @click="open = false" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                    <button wire:click="kirimPengajuan" class="flex-1 py-3 bg-teal-600 text-white font-black rounded-xl shadow-lg">Konfirmasi & Kirim</button>
                </div>
            </div>
        </div>
    </div>
</div>