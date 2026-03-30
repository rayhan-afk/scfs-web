<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto flex-1">
            <div class="relative w-full sm:max-w-md">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari barang di gudang..." class="pl-11 w-full rounded-2xl border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 py-3 text-sm shadow-sm transition-all">
            </div>

            <button wire:click="$toggle('filterKritis')" class="flex items-center justify-center gap-2 px-6 py-3 rounded-2xl font-bold text-sm transition-all shadow-sm {{ $filterKritis ? 'bg-red-500 text-white border-transparent' : 'bg-white text-gray-600 border border-gray-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                {{ $filterKritis ? 'Tampilkan Semua Produk' : 'Tampilkan Perlu Restock' }}
            </button>
        </div>
        
        <button wire:click="bukaModalTambah" class="w-full sm:w-auto bg-blue-600 text-white font-bold px-6 py-3 rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 flex items-center justify-center gap-2 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Produk
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Produk</p>
            <h3 class="text-3xl font-black text-gray-800">{{ $total_produk }}</h3>
        </div>
        <div wire:click="$set('filterKritis', true)" class="bg-white p-6 rounded-2xl shadow-sm border border-red-100 flex flex-col justify-center cursor-pointer hover:bg-red-50 transition-colors">
            <p class="text-xs font-bold text-red-500 uppercase tracking-widest mb-1">Stok Kritis</p>
            <h3 class="text-3xl font-black text-red-600">{{ $stok_menipis }}</h3>
        </div>
    </div>

    @if($filterKritis)
    <div class="bg-red-50 border border-red-100 p-4 rounded-xl flex items-center gap-3">
        <span class="text-red-600 bg-red-100 p-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </span>
        <div>
            <p class="text-sm font-bold text-red-800">Mode Peringatan Stok Aktif</p>
            <p class="text-xs text-red-600">Menampilkan produk yang sisa stoknya berada di bawah batas minimum. Gunakan tombol aksi (Audit Stok/Edit) untuk melakukan restock.</p>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Detail Produk</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Grosir</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Status Stok</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-widest">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($produks as $p)
                    <tr class="group hover:bg-blue-50/30 transition-all">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 border border-gray-100 flex-shrink-0">
                                    @if($p->foto_produk)
                                        <img src="{{ asset('storage/'.$p->foto_produk) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 group-hover:text-blue-600 transition-colors">{{ $p->nama_produk }}</p>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-0.5">{{ $p->sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-black text-gray-700">Rp {{ number_format($p->harga_grosir, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">Per Unit</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-black {{ $p->stok_sekarang <= $p->batas_minimum_stok ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ $p->stok_sekarang }}
                                </span>
                                <div class="flex-1 h-2 bg-gray-100 rounded-full w-24">
                                    <div class="h-2 rounded-full {{ $p->stok_sekarang <= $p->batas_minimum_stok ? 'bg-red-500 animate-pulse' : 'bg-green-500' }}" 
                                         style="width: {{ min(($p->stok_sekarang / max(1, $p->batas_minimum_stok * 2)) * 100, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center items-center gap-2">
                                <button wire:click="bukaModalOpname({{ $p->id }})" class="p-2 text-orange-500 hover:bg-orange-100 rounded-xl transition-colors" title="Audit Stok">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </button>
                                <button wire:click="editProduk({{ $p->id }})" class="p-2 text-blue-500 hover:bg-blue-100 rounded-xl transition-colors" title="Edit Data">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-16 text-center">
                            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                            <p class="text-sm font-bold text-gray-500">Belum ada produk di etalase Anda</p>
                            <p class="text-xs text-gray-400 mt-1">Klik "Tambah Produk Baru" untuk memulai.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-50 bg-white">
            {{ $produks->links() }}
        </div>
    </div>

    @if($showModalProduk)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[24px] shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl md:text-2xl font-black text-gray-800">{{ $isEdit ? 'Update Produk' : 'Tambah Produk Baru' }}</h3>
                <button wire:click="$set('showModalProduk', false)" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form wire:submit.prevent="simpanProduk" class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest">Foto Produk</label>
                    <div class="relative w-full aspect-square rounded-[20px] border-2 border-dashed border-gray-200 flex flex-col items-center justify-center overflow-hidden bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer group">
                        @if ($foto_produk)
                            <img src="{{ $foto_produk->temporaryUrl() }}" class="w-full h-full object-cover">
                        @elseif($foto_produk_lama)
                            <img src="{{ asset('storage/'.$foto_produk_lama) }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-10 h-10 text-gray-300 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <p class="text-xs text-gray-400 font-bold">Klik untuk upload foto</p>
                        @endif
                        <input type="file" wire:model="foto_produk" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                    </div>
                    @error('foto_produk') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-blue-600 uppercase tracking-widest mb-1.5">SKU Produk</label>
                            <input type="text" wire:model="sku" class="w-full rounded-xl border-gray-200 bg-gray-50 font-mono text-sm uppercase text-gray-500" readonly>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Harga Grosir (Rp)</label>
                            <input type="number" wire:model="harga_grosir" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm" placeholder="0">
                            @error('harga_grosir') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Nama Produk</label>
                        <input type="text" wire:model="nama_produk" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm" placeholder="Contoh: Beras Premium 5kg">
                        @error('nama_produk') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Stok Saat Ini</label>
                            <input type="number" wire:model="stok_sekarang" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm" placeholder="0">
                            @error('stok_sekarang') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Satuan</label>
                            <select wire:model="satuan" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm bg-white">
                                <option value="pcs">Pcs</option>
                                <option value="kg">Kilogram (Kg)</option>
                                <option value="gram">Gram (g)</option>
                                <option value="liter">Liter (L)</option>
                                <option value="dus">Karton / Dus</option>
                                <option value="pack">Pack / Bungkus</option>
                                <option value="lusin">Lusin</option>
                            </select>
                            @error('satuan') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Batas Minimum</label>
                            <input type="number" wire:model="batas_minimum_stok" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm" placeholder="5">
                            @error('batas_minimum_stok') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Deskripsi Singkat</label>
                        <textarea wire:model="deskripsi" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm" rows="3" placeholder="Jelaskan detail produk..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showModalProduk', false)" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all text-sm">
                            {{ $isEdit ? 'Simpan Perubahan' : 'Terbitkan Produk' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if($showModalOpname)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[24px] shadow-2xl w-full max-w-md p-6">
            <h3 class="text-xl font-black text-gray-800 mb-1">Audit Stok (Opname)</h3>
            <p class="text-sm text-gray-500 mb-6">Penyesuaian stok untuk: <b class="text-gray-800">{{ $nama_produk }}</b></p>
            
            <form wire:submit.prevent="simpanOpname" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Stok Tercatat di Sistem</label>
                    <input type="text" disabled value="{{ $stok_sekarang }} Unit" class="w-full bg-gray-50 border-gray-200 rounded-xl text-gray-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-blue-600 uppercase tracking-widest mb-1.5">Stok Fisik Sebenarnya</label>
                    <input type="number" wire:model="stok_fisik" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 py-3 font-black text-xl text-gray-800" placeholder="0">
                    @error('stok_fisik') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Keterangan (Opsional)</label>
                    <textarea wire:model="keterangan_opname" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm py-2" rows="2" placeholder="Contoh: Salah hitung, barang expired..."></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" wire:click="$set('showModalOpname', false)" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all text-sm">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all text-sm">Update Stok</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    
</div>