<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\MerchantProduct;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    use WithFileUploads;

    // State Modal & Form
    public $isModalOpen = false;
    public $editId = null;
    
    public $nama_produk = '';
    public $kategori = 'makanan';
    public $harga_pokok = '';
    public $harga_jual = '';
    public $is_tersedia = true;
    
    public $foto_produk_baru;
    public $existing_foto;

    // Filter UI
    public $search = '';
    public $filterKategori = 'semua';

    #[Computed]
    public function products()
    {
        $query = MerchantProduct::where('merchant_id', Auth::id());

        if ($this->search) {
            $query->where('nama_produk', 'like', '%' . $this->search . '%');
        }

        if ($this->filterKategori !== 'semua') {
            $query->where('kategori', $this->filterKategori);
        }

        return $query->latest()->get();
    }

    public function openModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['foto_produk_baru']); // Bersihkan file upload sebelumnya
        
        if ($id) {
            // Mode Edit: Validasi Strict Anti-IDOR
            $product = MerchantProduct::where('merchant_id', Auth::id())->findOrFail($id);
            $this->editId = $product->id;
            $this->nama_produk = $product->nama_produk;
            $this->kategori = $product->kategori;
            $this->harga_pokok = $product->harga_pokok;
            $this->harga_jual = $product->harga_jual;
            $this->is_tersedia = $product->is_tersedia;
            $this->existing_foto = $product->foto_produk;
        } else {
            // Mode Tambah Baru
            $this->reset(['editId', 'nama_produk', 'kategori', 'harga_pokok', 'harga_jual', 'existing_foto']);
            $this->is_tersedia = true;
        }
        
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->reset(['editId', 'nama_produk', 'kategori', 'harga_pokok', 'harga_jual', 'is_tersedia', 'foto_produk_baru', 'existing_foto']);
    }

    public function save()
    {
        // 1. Validasi Input Ketat
        $this->validate([
            'nama_produk' => 'required|string|max:100',
            'kategori'    => 'required|in:makanan,minuman,barang_koperasi,lainnya',
            'harga_pokok' => 'required|numeric|min:0',
            'harga_jual'  => 'required|numeric|min:500|gt:harga_pokok', // Harga Jual HARUS lebih besar dari Pokok
            'foto_produk_baru' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
        ], [
            'harga_jual.gt' => 'Harga jual harus lebih besar dari harga modal (pokok).',
            'foto_produk_baru.image' => 'File harus berupa gambar.',
            'foto_produk_baru.max' => 'Ukuran gambar maksimal 2MB.'
        ]);

        $updateData = [
            'merchant_id' => Auth::id(), 
            'nama_produk' => $this->nama_produk,
            'kategori'    => $this->kategori,
            'harga_pokok' => $this->harga_pokok,
            'harga_jual'  => $this->harga_jual,
            'is_tersedia' => $this->is_tersedia,
        ];

        // 2. Storage Image Optimization
        if ($this->foto_produk_baru) {
            if ($this->editId && $this->existing_foto && Storage::disk('public')->exists($this->existing_foto)) {
                Storage::disk('public')->delete($this->existing_foto);
            }
            $updateData['foto_produk'] = $this->foto_produk_baru->store('merchants/products', 'public');
        }

        // 3. LOGIKA AUDIT TRAIL (MATA-MATA PERUBAHAN HARGA)
        $productLama = MerchantProduct::find($this->editId); // Cari produk sebelum di-update

        $hargaBerubah = false;
        $hargaPokokLama = null;
        $hargaJualLama = null;

        if ($productLama) {
            // Cek apakah angkanya diganti oleh merchant?
            if ($productLama->harga_pokok != $this->harga_pokok || $productLama->harga_jual != $this->harga_jual) {
                $hargaBerubah = true;
                $hargaPokokLama = $productLama->harga_pokok;
                $hargaJualLama = $productLama->harga_jual;
            }
        } else {
            // Jika ini produk baru, otomatis catat perubahan dari Rp 0 ke Harga Baru
            $hargaBerubah = true;
        }

        // Eksekusi Update atau Create Data Katalog Utama
        $savedProduct = MerchantProduct::updateOrCreate(
            ['id' => $this->editId, 'merchant_id' => Auth::id()],
            $updateData
        );

        // Jika harga benar-benar berubah, suntikkan ke tabel History untuk dilihat Admin
        if ($hargaBerubah) {
            \App\Models\ProductPriceHistory::create([
                'merchant_product_id' => $savedProduct->id,
                'harga_pokok_lama'    => $hargaPokokLama,
                'harga_pokok_baru'    => $this->harga_pokok,
                'harga_jual_lama'     => $hargaJualLama,
                'harga_jual_baru'     => $this->harga_jual,
            ]);
        }

        $this->closeModal();
        session()->flash('success', 'Produk berhasil disimpan ke katalog!');
    }

    public function toggleStatus($id)
    {
        $product = MerchantProduct::where('merchant_id', Auth::id())->findOrFail($id);
        $product->update(['is_tersedia' => !$product->is_tersedia]);
    }

    public function delete($id)
    {
        $product = MerchantProduct::where('merchant_id', Auth::id())->findOrFail($id);
        
        // Storage Garbage Collection: Hapus gambar fisik sebelum hapus record DB
        if ($product->foto_produk && Storage::disk('public')->exists($product->foto_produk)) {
            Storage::disk('public')->delete($product->foto_produk);
        }
        
        $product->delete();
        session()->flash('success', 'Produk beserta gambarnya berhasil dihapus permanen.');
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    {{-- Header & Action --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Katalog Produk (POS)</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola menu, gambar, dan margin profit untuk Mesin Kasir Anda.</p>
        </div>
        <button wire:click="openModal" class="px-4 py-2.5 bg-emerald-600 border border-emerald-200 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center gap-2 hover:bg-emerald-100 hover:text-emerald-700 group">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Produk Baru
        </button>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6 animate-pulse">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Filter & Search --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-4">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama produk..." 
                class="w-full py-3 pl-11 pr-4 text-sm text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100 transition">
        </div>
        <div class="flex gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
            @foreach(['semua' => 'Semua', 'makanan' => 'Makanan', 'minuman' => 'Minuman', 'barang_koperasi' => 'Koperasi'] as $val => $label)
                <button wire:click="$set('filterKategori', '{{ $val }}')" 
                    class="px-4 py-2.5 text-xs font-bold rounded-xl whitespace-nowrap transition-colors border 
                    {{ $filterKategori === $val ? 'bg-emerald-50 border-emerald-200 text-emerald-700 shadow-sm' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Grid Katalog (Gaya Visual POS) --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
        @forelse($this->products as $item)
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden flex flex-col relative">
            
            {{-- Image Area --}}
            <div class="relative h-36 w-full bg-gray-100 overflow-hidden">
                @if($item->foto_produk)
                    <img src="{{ asset('storage/' . $item->foto_produk) }}" alt="{{ $item->nama_produk }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 {{ !$item->is_tersedia ? 'grayscale opacity-60' : '' }}">
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300 {{ !$item->is_tersedia ? 'opacity-50' : '' }}">
                        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                @endif

                {{-- Badges --}}
                <div class="absolute top-2 left-2">
                    <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-1 rounded-md shadow-sm backdrop-blur-sm
                        {{ $item->kategori == 'makanan' ? 'bg-orange-500/90 text-white' : ($item->kategori == 'minuman' ? 'bg-blue-500/90 text-white' : 'bg-purple-500/90 text-white') }}">
                        {{ str_replace('_', ' ', $item->kategori) }}
                    </span>
                </div>
                <div class="absolute top-2 right-2">
                    <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex items-center h-5 rounded-full w-9 transition-colors shadow-sm focus:outline-none {{ $item->is_tersedia ? 'bg-emerald-500' : 'bg-rose-500' }}">
                        <span class="inline-block w-3.5 h-3.5 transform bg-white rounded-full transition-transform {{ $item->is_tersedia ? 'translate-x-4.5' : 'translate-x-1' }}"></span>
                    </button>
                </div>
            </div>

            {{-- Info Area --}}
            <div class="p-4 flex-1 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 leading-tight mb-2 {{ !$item->is_tersedia ? 'opacity-50 line-through' : '' }}">{{ $item->nama_produk }}</h3>
                </div>
                
                <div class="mt-2 space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] text-gray-500 font-medium">H. Pokok</span>
                        <span class="text-[10px] text-gray-600 font-bold">Rp{{ number_format($item->harga_pokok, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] text-gray-500 font-medium">H. Jual</span>
                        <span class="text-xs text-emerald-600 font-extrabold">Rp{{ number_format($item->harga_jual, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-1 border-t border-gray-100 mt-1">
                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Est. Profit</span>
                        <span class="text-[10px] text-blue-600 font-bold">+Rp{{ number_format($item->harga_jual - $item->harga_pokok, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            
            {{-- Hover Actions --}}
            <div class="absolute bottom-0 left-0 w-full px-4 py-3 bg-white/90 backdrop-blur-sm border-t border-gray-100 flex justify-between gap-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                <button wire:click="openModal({{ $item->id }})" class="text-xs font-extrabold text-blue-600 hover:text-blue-800 transition bg-blue-50 px-3 py-1.5 rounded-lg w-full">Edit</button>
                <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus gambar dan produk ini permanen?" class="text-xs font-extrabold text-rose-500 hover:text-rose-700 transition bg-rose-50 px-3 py-1.5 rounded-lg w-full">Hapus</button>
            </div>
        </div>
        @empty
        <div class="col-span-full py-24 text-center border-2 border-dashed border-gray-200 rounded-3xl bg-gray-50/50">
            <div class="text-5xl mb-4 opacity-30">📋</div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Katalog Produk Kosong</h3>
            <p class="text-gray-500 text-sm">Tambahkan produk beserta gambarnya untuk memulai sistem Kasir POS.</p>
            <button wire:click="openModal" class="mt-4 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition shadow-sm">
                + Tambah Produk Perdana
            </button>
        </div>
        @endforelse
    </div>

    {{-- MODAL CREATE / EDIT --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 flex-shrink-0">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    {{ $editId ? 'Edit Produk POS' : 'Tambah Produk POS' }}
                </h3>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="save" class="overflow-y-auto flex-1 p-6 space-y-6">
                
                {{-- Area Upload Foto --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Foto Produk (Thumbnail POS)</label>
                    <div class="flex items-center gap-5">
                        <div class="h-20 w-20 rounded-2xl bg-gray-100 border-2 border-dashed border-gray-300 overflow-hidden flex items-center justify-center flex-shrink-0">
                            @if($foto_produk_baru)
                                <img src="{{ $foto_produk_baru->temporaryUrl() }}" class="w-full h-full object-cover">
                            @elseif($existing_foto)
                                <img src="{{ asset('storage/' . $existing_foto) }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input wire:model="foto_produk_baru" type="file" accept="image/jpeg,image/png,image/jpg,image/webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                            <div wire:loading wire:target="foto_produk_baru" class="text-[10px] font-bold text-emerald-600 mt-2 animate-pulse">Memproses gambar...</div>
                            @error('foto_produk_baru') <span class="text-rose-500 text-[10px] mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Kategori & Nama --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Kategori Produk</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="kategori" value="makanan" class="peer sr-only">
                            <div class="text-center px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition shadow-sm">🍔 Makanan</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="kategori" value="minuman" class="peer sr-only">
                            <div class="text-center px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition shadow-sm">🥤 Minuman</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="kategori" value="barang_koperasi" class="peer sr-only">
                            <div class="text-center px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition shadow-sm">👕 Koperasi</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="kategori" value="lainnya" class="peer sr-only">
                            <div class="text-center px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition shadow-sm">📦 Lainnya</div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Menu / Produk</label>
                    <input wire:model="nama_produk" type="text" placeholder="Cth: Ayam Geprek Spesial" 
                        class="w-full py-3 px-4 text-sm font-medium text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 transition">
                    @error('nama_produk') <span class="text-rose-500 text-[10px] mt-1 font-bold block">{{ $message }}</span> @enderror
                </div>

                {{-- Area Harga & Profit Calculation --}}
                <div class="grid grid-cols-2 gap-5 bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                    <div>
                        <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1.5">Harga Pokok (Modal)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 font-bold text-xs">Rp</span>
                            <input wire:model.live.debounce.500ms="harga_pokok" type="number" step="500" placeholder="0" 
                                class="w-full py-2.5 pl-10 pr-3 text-sm font-bold text-gray-900 bg-white border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        </div>
                        @error('harga_pokok') <span class="text-rose-500 text-[9px] mt-1 font-bold block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1.5">Harga Jual (POS)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 font-bold text-xs">Rp</span>
                            <input wire:model.live.debounce.500ms="harga_jual" type="number" step="500" placeholder="0" 
                                class="w-full py-2.5 pl-10 pr-3 text-sm font-bold text-gray-900 bg-white border border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition">
                        </div>
                        @error('harga_jual') <span class="text-rose-500 text-[9px] mt-1 font-bold block">{{ $message }}</span> @enderror
                    </div>
                    
                    {{-- Realtime Profit Calculator (Frontend Magic) --}}
                    @if((int)$harga_jual > 0 && (int)$harga_pokok >= 0 && ((int)$harga_jual > (int)$harga_pokok))
                    <div class="col-span-2 pt-3 border-t border-blue-100 flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-600">Estimasi Profit Kotor / Porsi:</span>
                        <span class="text-sm font-extrabold text-blue-600 bg-blue-100 px-3 py-1 rounded-lg">
                            + Rp {{ number_format((int)$harga_jual - (int)$harga_pokok, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif
                </div>

            </form>
            
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 flex-shrink-0">
                <button type="button" wire:click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="save" wire:loading.attr="disabled" class="px-6 py-2.5 text-sm font-extrabold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-lg shadow-emerald-200 focus:ring-4 focus:ring-emerald-100 disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">Simpan Menu</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>