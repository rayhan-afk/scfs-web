<div class="p-6 relative pb-32">
    
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Tarik Dana Pendapatan</h1>
        <p class="text-sm text-gray-500 mt-1">Tarik saldo dari pesanan yang sudah selesai ke rekening bank Anda.</p>
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

    {{-- Widget Saldo Pendapatan --}}
    <div class="bg-gradient-to-r from-blue-600 to-indigo-800 rounded-3xl p-6 text-white shadow-lg mb-8 relative overflow-hidden">
        <svg class="absolute right-0 top-0 w-64 h-64 text-white opacity-10 transform translate-x-16 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1h-2V5H5v14h14v-1h2zm-2-7v-2h-6V7h-2v2H5v2h6v2h2v-2h6z"></path></svg>

        <div class="relative z-10 flex flex-col md:flex-row gap-6 justify-between items-center">
            <div class="w-full md:w-1/2">
                <p class="text-blue-100 font-medium text-sm mb-1 uppercase tracking-wider">Saldo Siap Ditarik</p>
                <h2 class="text-4xl font-black mb-4">Rp {{ number_format($saldoTersedia, 0, ',', '.') }}</h2>
                
                <div class="flex gap-4 text-xs text-blue-100 font-medium bg-white/10 p-3 rounded-xl inline-flex backdrop-blur-sm border border-white/20">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-orange-400"></div>
                        <span>Saldo Ditahan (PO Berjalan): Rp {{ number_format($saldoDitahan, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-white/20 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs text-blue-100 uppercase font-bold">Rekening Penerima</p>
                        <p class="font-black text-sm truncate max-w-[150px]">{{ optional($pemasokProfile)->info_bank ?? 'Belum Diatur' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Status Penarikan --}}
    <div class="flex overflow-x-auto space-x-2 bg-white p-1.5 rounded-2xl w-full mb-6 border border-gray-100 shadow-sm hide-scrollbar">
        @foreach(['siap_ditarik' => '1. Siap Ditarik', 'diproses' => '2. Sedang Diproses', 'berhasil' => '3. Berhasil Ditarik'] as $key => $label)
            <button wire:click="setTab('{{ $key }}')" class="flex-none px-5 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === $key ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Daftar Saldo / Transaksi --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-12">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        @if($activeTab === 'siap_ditarik') <th class="p-4 w-12 text-center">Pilih</th> @endif
                        <th class="p-4 font-bold">ID Transaksi</th>
                        <th class="p-4 font-bold">Sumber Pendapatan</th>
                        <th class="p-4 font-bold">Tanggal</th>
                        <th class="p-4 font-bold text-right">Nominal</th>
                        @if(in_array($activeTab, ['diproses', 'berhasil'])) <th class="p-4 font-bold text-center">ID Penarikan (Bank)</th> @endif
                        @if($activeTab === 'diproses') <th class="p-4 font-bold text-center">Tindakan Demo</th> @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dataDitampilkan as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            @if($activeTab === 'siap_ditarik')
                                <td class="p-4 text-center">
                                    <input type="checkbox" wire:model.live="selectedPendapatan" value="{{ $item['id'] }}" class="w-5 h-5 text-blue-600 rounded cursor-pointer border-gray-300 focus:ring-blue-500">
                                </td>
                            @endif
                            <td class="p-4 font-black text-gray-800 text-sm">{{ $item['id'] }}</td>
                            <td class="p-4 text-sm font-medium text-gray-600">{{ $item['sumber'] }}</td>
                            <td class="p-4 text-sm text-gray-500">{{ $item['tanggal'] }}</td>
                            <td class="p-4 font-black text-gray-900 text-right text-blue-600">+ Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                            
                            @if(in_array($activeTab, ['diproses', 'berhasil']))
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $activeTab === 'berhasil' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                        {{ $item['id_penarikan'] }}
                                    </span>
                                </td>
                            @endif

                            @if($activeTab === 'diproses')
                                <td class="p-4 text-center">
                                    {{-- Tombol ini hanya untuk mensimulasikan pencairan sukses dari sisi sistem --}}
                                    <button wire:click="konfirmasiTransferSelesai('{{ $item['id_penarikan'] }}')" class="px-3 py-1.5 bg-emerald-600 text-white text-[10px] uppercase font-bold rounded-lg hover:bg-emerald-700">Simulasi Cair</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $activeTab === 'siap_ditarik' ? 5 : 6 }}" class="p-10 text-center text-gray-400 font-medium">Tidak ada riwayat saldo di kategori ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- FLOATING ACTION BAR --}}
    @if(count($selectedPendapatan) > 0 && $activeTab === 'siap_ditarik')
        <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 w-[90%] md:w-auto bg-gray-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex flex-col md:flex-row items-center justify-between gap-8 z-40 border border-gray-700">
            <div class="flex items-center gap-6">
                <div>
                    <span class="block text-xs text-gray-400 font-bold uppercase">Dipilih</span>
                    <span class="block text-lg font-black text-blue-400">{{ count($selectedPendapatan) }} Transaksi</span>
                </div>
                <div class="h-8 w-px bg-gray-700"></div>
                <div>
                    <span class="block text-xs text-gray-400 font-bold uppercase">Total Ditarik</span>
                    <span class="block text-xl font-black">Rp {{ number_format($this->totalPenarikan, 0, ',', '.') }}</span>
                </div>
            </div>
            <button wire:click="$set('showModalKonfirmasi', true)" class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl shadow-lg hover:bg-blue-700 transition-all flex items-center gap-2">
                Tarik ke Bank
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </button>
        </div>
    @endif

    {{-- MODAL KONFIRMASI TARIK DANA --}}
    <div x-data="{ open: @entangle('showModalKonfirmasi') }" 
         x-show="open" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4" 
         x-cloak>
        
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

        <div x-show="open" x-transition.scale class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden z-[70] relative">
            <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                <h3 class="text-xl font-black text-gray-800">Konfirmasi Penarikan</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 space-y-6">
                {{-- Rincian Tarik --}}
                <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 flex justify-between items-center">
                    <span class="text-sm font-bold text-blue-900">Total Penarikan</span>
                    <span class="text-2xl font-black text-blue-700">Rp {{ number_format($this->totalPenarikan, 0, ',', '.') }}</span>
                </div>

                {{-- Bagian Rekening --}}
                <div class="space-y-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dikirim ke Rekening Pemasok</label>
                    <div class="border border-gray-200 p-4 rounded-2xl flex items-center gap-4 bg-white">
                        <div class="bg-gray-100 p-2.5 rounded-xl border border-gray-200">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <div class="flex-1 text-sm">
                            <p class="font-black text-gray-800 leading-tight">
                                {{ optional($pemasokProfile)->info_bank ?? 'Rekening belum diatur' }}
                            </p>
                            <p class="text-[11px] text-gray-500 mt-1 font-medium">Proses pencairan memakan waktu 1x24 Jam kerja.</p>
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 text-[11px] text-gray-500">
                    <p>Pastikan nomor rekening Anda sudah benar. Kesalahan penginputan nomor rekening bukan tanggung jawab sistem.</p>
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex gap-3">
                    <button @click="open = false" class="flex-1 py-3 bg-white border border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50">Batal</button>
                    <button wire:click="prosesTarikDana" class="flex-1 py-3 bg-blue-600 text-white font-black rounded-xl shadow-lg hover:bg-blue-700">Tarik Sekarang</button>
                </div>
            </div>
        </div>
    </div>
</div>