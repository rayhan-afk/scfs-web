<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800">Laporan & Analitik</h1>
            <p class="text-sm font-bold text-gray-500 mt-1">Pantau performa penjualan dan pergerakan stok gudang Anda.</p>
        </div>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <select wire:model.live="periode" class="bg-white border-gray-200 rounded-2xl font-bold text-sm text-gray-600 focus:ring-blue-500 py-3 shadow-sm w-full sm:w-auto">
                <option value="hari_ini">Hari Ini</option>
                <option value="bulan_ini">Bulan Ini</option>
                <option value="tahun_ini">Tahun Ini</option>
            </select>
            
            <button wire:click="downloadLaporan" class="w-full sm:w-auto bg-orange-600 text-white font-bold px-6 py-3 rounded-2xl hover:bg-orange-700 transition-all shadow-lg shadow-orange-200 flex items-center justify-center gap-2 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Buat Laporan
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-blue-50 border border-blue-200 text-blue-600 px-4 py-3 rounded-xl text-sm font-bold">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex space-x-2 bg-gray-100 p-1.5 rounded-2xl w-full sm:w-max mb-6">
        <button wire:click="setTab('penjualan')" class="flex-1 sm:flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'penjualan' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Laporan Penjualan
        </button>
        <button wire:click="setTab('stok')" class="flex-1 sm:flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'stok' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Pergerakan Stok
        </button>
    </div>

    @if($activeTab === 'penjualan')
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-orange-600 to-orange-800 p-6 rounded-[24px] shadow-lg shadow-orange-200 text-white">
                <p class="text-xs font-bold text-orange-200 uppercase tracking-widest mb-1">Total Pendapatan</p>
                <h3 class="text-3xl font-black">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</h3>
            </div>
            <div class="bg-white p-6 rounded-[24px] shadow-sm border border-gray-100 flex flex-col justify-center">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Pesanan Sukses</p>
                <h3 class="text-3xl font-black text-gray-800">{{ $totalPesanan }} <span class="text-sm text-gray-400 font-bold">Pesanan</span></h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[24px] shadow-sm border border-gray-100">
            <h3 class="text-lg font-black text-gray-800 mb-6">Tren Penjualan per Kategori</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div class="aspect-square max-h-64 bg-gray-50 rounded-full flex items-center justify-center border-4 border-dashed border-gray-200">
                    <p class="text-sm font-bold text-gray-400 text-center px-4">Render Pie Chart JS Anda di Sini<br>(Sesuai gambar dashboard)</p>
                </div>
                
                <div class="space-y-5">
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-gray-700 flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-blue-500"></div> Makanan</span>
                            <span class="text-sm font-black text-gray-900">45%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5"><div class="bg-blue-500 h-2.5 rounded-full" style="width: 45%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-gray-700 flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-sky-400"></div> Kosmetik</span>
                            <span class="text-sm font-black text-gray-900">30%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5"><div class="bg-sky-400 h-2.5 rounded-full" style="width: 30%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-gray-700 flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-gray-300"></div> Lainnya (ATK, dll)</span>
                            <span class="text-sm font-black text-gray-900">25%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5"><div class="bg-gray-300 h-2.5 rounded-full" style="width: 25%"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($activeTab === 'stok')
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
        <div class="bg-white rounded-[24px] shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-black text-gray-800">Riwayat Keluar/Masuk Barang</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-max">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Waktu</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Produk</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-widest">Jenis</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-widest">Jumlah</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($riwayatStok as $riwayat)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-gray-600">{{ \Carbon\Carbon::parse($riwayat['tanggal'])->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 text-sm font-black text-gray-800">{{ $riwayat['produk'] }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($riwayat['jenis'] === 'masuk')
                                    <span class="px-3 py-1 bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-wider rounded-lg">Masuk</span>
                                @else
                                    <span class="px-3 py-1 bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-wider rounded-lg">Keluar</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-black {{ $riwayat['jenis'] === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $riwayat['jenis'] === 'masuk' ? '+' : '-' }}{{ $riwayat['jumlah'] }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-500">{{ $riwayat['keterangan'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-sm font-bold text-gray-400">Belum ada riwayat pergerakan stok.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>