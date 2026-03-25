<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800">Pesanan Masuk</h1>
            <p class="text-sm font-bold text-gray-500 mt-1">Kelola pesanan dari jaringan Merchant Anda.</p>
        </div>
        
        <div class="w-full sm:w-auto relative">
            <input type="text" wire:model.live="search" placeholder="Cari ID Pesanan / Merchant..." class="w-full sm:w-80 pl-10 pr-4 py-2.5 bg-white border-gray-200 rounded-2xl text-sm focus:ring-blue-500 shadow-sm font-bold text-gray-600">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="flex overflow-x-auto space-x-2 bg-gray-100 p-1.5 rounded-2xl w-full sm:w-max hide-scrollbar">
        <button wire:click="setTab('baru')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'baru' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Pesanan Baru
            @if($activeTab !== 'baru') <span class="ml-1 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full">1</span> @endif
        </button>
        <button wire:click="setTab('diproses')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'diproses' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Diproses / Dikirim
        </button>
        <button wire:click="setTab('selesai')" class="flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all {{ $activeTab === 'selesai' ? 'bg-white text-green-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
            Selesai
        </button>
    </div>

    <div class="space-y-4">
        @forelse($daftarPesanan as $order)
        <div class="bg-white rounded-[24px] shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row gap-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            
            <div class="flex-1 space-y-4">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-black tracking-wider rounded-lg">{{ $order['id'] }}</span>
                    <span class="text-xs font-bold text-gray-400">{{ $order['tanggal'] }}</span>
                </div>
                
                <div>
                    <h3 class="text-lg font-black text-gray-800">{{ $order['merchant'] }}</h3>
                    <p class="text-sm font-bold text-gray-500 mt-1 flex items-start gap-1.5">
                        <svg class="w-4 h-4 mt-0.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        {{ $order['alamat'] }}
                    </p>
                </div>

                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-sm font-bold text-gray-600"><span class="text-gray-400 uppercase tracking-widest text-xs mb-1 block">Item Dipesan:</span> {{ $order['item'] }}</p>
                </div>
            </div>

            <div class="flex flex-col justify-between items-start md:items-end border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6 min-w-[200px]">
                <div class="text-left md:text-right w-full mb-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Pembayaran</p>
                    <h3 class="text-2xl font-black text-blue-600">Rp {{ number_format($order['total_harga'], 0, ',', '.') }}</h3>
                </div>

                <div class="w-full flex flex-col gap-2">
                    @if($activeTab === 'baru')
                        <button wire:click="updateStatusPesanan('{{ $order['id'] }}', 'diproses')" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-xl hover:bg-blue-700 transition-all text-sm shadow-md shadow-blue-200">
                            Terima & Proses Pesanan
                        </button>
                    @elseif($activeTab === 'diproses')
                        <button wire:click="updateStatusPesanan('{{ $order['id'] }}', 'selesai')" class="w-full bg-orange-500 text-white font-bold py-2.5 rounded-xl hover:bg-orange-600 transition-all text-sm shadow-md shadow-orange-200">
                            Tandai Sudah Dikirim
                        </button>
                    @elseif($activeTab === 'selesai')
                        <button class="w-full bg-gray-100 text-gray-500 font-bold py-2.5 rounded-xl text-sm cursor-not-allowed">
                            Pesanan Selesai
                        </button>
                    @endif
                    <button class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all text-sm">
                        Lihat Detail
                    </button>
                </div>
            </div>

        </div>
        @empty
        <div class="text-center py-16 bg-white rounded-[24px] border border-gray-100 border-dashed">
            <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            </div>
            <h3 class="text-lg font-black text-gray-800">Belum Ada Pesanan</h3>
            <p class="text-sm font-bold text-gray-500 mt-1">Tidak ada pesanan di kategori ini.</p>
        </div>
        @endforelse
    </div>

</div>