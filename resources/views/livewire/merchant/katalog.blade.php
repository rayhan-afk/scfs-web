<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\MerchantProduct;
use App\Models\SupplyOrderDetail;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    use WithFileUploads;

    public $isModalOpen = false;
    public $editId = null;
    public $processing_po_detail_id = null; 
    
    public $nama_produk = '';
    public $kategori = 'makanan';
    public $harga_pokok = ''; 
    public $harga_jual = '';
    public $is_tersedia = 1;
    public $stok = 0; 
    public $max_stok = null; 
    
    public $foto_produk_baru;
    public $existing_foto;

    public $search = '';
    public $filterKategori = 'semua';
    public $tabGudang = 'tersedia'; 

    #[Computed]
    public function poTersedia()
    {
        return SupplyOrderDetail::with(['supplyOrder.pemasok.pemasokProfile', 'produkPemasok'])
            ->whereHas('supplyOrder', function($q) {
                $q->where('merchant_id', Auth::id())->where('status', 'selesai');
            })
            ->where('is_added_to_pos', false)
            ->latest()
            ->get(); 
    }

    #[Computed]
    public function poHabis()
    {
        return SupplyOrderDetail::with(['supplyOrder.pemasok.pemasokProfile', 'produkPemasok'])
            ->whereHas('supplyOrder', function($q) {
                $q->where('merchant_id', Auth::id())->where('status', 'selesai');
            })
            ->where('is_added_to_pos', true)
            ->latest()
            ->take(10)
            ->get(); 
    }

    #[Computed]
    public function products()
    {
        $query = MerchantProduct::where('merchant_id', Auth::id());
        if ($this->search) $query->where('nama_produk', 'like', '%' . $this->search . '%');
        if ($this->filterKategori !== 'semua') $query->where('kategori', $this->filterKategori);
        return $query->latest()->get();
    }

    public function updatedStok($value)
    {
        if ($this->max_stok !== null && (int)$value > $this->max_stok) {
            $this->stok = $this->max_stok;
            session()->flash('error_stok', 'Dilarang memanipulasi stok! Maksimal stok adalah ' . $this->max_stok);
        }
    }

    public function openModalManual()
    {
        $this->resetValidation();
        $this->reset(['editId', 'nama_produk', 'harga_pokok', 'harga_jual', 'existing_foto', 'foto_produk_baru', 'max_stok', 'processing_po_detail_id']);
        $this->kategori = 'makanan';
        $this->stok = 0; 
        $this->is_tersedia = 1;
        $this->isModalOpen = true;
    }

    public function jadikanMenu($detailId)
    {
        $this->resetValidation();
        $this->reset(['foto_produk_baru', 'existing_foto']);
        
        $detail = SupplyOrderDetail::with('produkPemasok')->findOrFail($detailId);
        $this->processing_po_detail_id = $detailId;
        
        $existingProduct = MerchantProduct::where('merchant_id', Auth::id())
                            ->where('nama_produk', $detail->nama_produk_snapshot)
                            ->first();

        if ($existingProduct) {
            // ALUR RESTOCK DARI PO: Tambahkan qty PO ke stok saat ini
            $this->editId = $existingProduct->id;
            $this->kategori = $existingProduct->kategori;
            $this->stok = $existingProduct->stok + $detail->qty; 
            $this->max_stok = $this->stok; // KUNCI!
            $this->harga_jual = $existingProduct->harga_jual;
            $this->is_tersedia = $existingProduct->is_tersedia;
            $this->existing_foto = $existingProduct->foto_produk;
        } else {
            // ALUR BARU DARI PO
            $this->editId = null;
            $this->kategori = 'makanan'; 
            $this->stok = $detail->qty; 
            $this->max_stok = $detail->qty; // KUNCI SESUAI QTY PO!
            $this->harga_jual = $detail->harga_modal_snapshot + $detail->margin_pemasok_snapshot; 
            $this->is_tersedia = 1;
            $this->existing_foto = $detail->produkPemasok->foto_produk ?? null; 
        }

        $this->nama_produk = $detail->nama_produk_snapshot;
        $this->harga_pokok = $detail->harga_modal_snapshot + $detail->margin_pemasok_snapshot;
        $this->isModalOpen = true;
    }

    public function openModalEdit($id)
    {
        $this->resetValidation();
        $this->reset(['foto_produk_baru', 'processing_po_detail_id']); 
        
        $product = MerchantProduct::where('merchant_id', Auth::id())->findOrFail($id);
        $this->editId = $product->id;
        $this->nama_produk = $product->nama_produk;
        $this->kategori = $product->kategori;
        $this->harga_pokok = $product->harga_pokok;
        $this->harga_jual = $product->harga_jual;
        $this->is_tersedia = $product->is_tersedia;
        
        // KUNCI MUTLAK: Saat tombol "Edit" diklik, stok MAKSIMAL adalah stok sisa saat ini!
        // Merchant dilarang keras menambah stok lewat form Edit.
        $this->stok = $product->stok ?? 0; 
        $this->max_stok = $this->stok; 
        
        $this->existing_foto = $product->foto_produk;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function save()
    {
        $rules = [
            'nama_produk' => 'required|string|max:100',
            'kategori'    => 'required|in:makanan,minuman,barang_koperasi,lainnya',
            'stok'        => 'required|integer|min:0',
            'harga_pokok' => 'required|numeric|min:0', 
            'harga_jual'  => 'required|numeric|min:0|gte:harga_pokok',
            'foto_produk_baru' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', 
        ];

        if ($this->max_stok !== null) {
            $rules['stok'] .= '|max:' . $this->max_stok;
        }

        $this->validate($rules, [
            'stok.max' => 'Sistem Menolak! Stok melebihi batas (' . $this->max_stok . '). Penambahan stok baru WAJIB lewat Inbound Logistik PO.',
        ]);

        $updateData = [
            'merchant_id' => Auth::id(), 
            'nama_produk' => $this->nama_produk,
            'kategori'    => $this->kategori,
            'stok'        => (int) $this->stok, 
            'harga_pokok' => (int) $this->harga_pokok,
            'harga_jual'  => (int) $this->harga_jual,
            'is_tersedia' => $this->is_tersedia ? 1 : 0,
        ];

        if ($this->foto_produk_baru) {
            if ($this->editId && $this->existing_foto && Storage::disk('public')->exists($this->existing_foto)) {
                Storage::disk('public')->delete($this->existing_foto);
            }
            $updateData['foto_produk'] = $this->foto_produk_baru->store('merchants/products', 'public');
        } else {
            $updateData['foto_produk'] = $this->existing_foto; 
        }

        MerchantProduct::updateOrCreate(['id' => $this->editId, 'merchant_id' => Auth::id()], $updateData);

        // Jika ini dari PO Gudang, ubah statusnya jadi Habis (Sudah masuk POS)
        if ($this->processing_po_detail_id) {
            SupplyOrderDetail::where('id', $this->processing_po_detail_id)->update(['is_added_to_pos' => true]);
        }

        $this->closeModal();
        session()->flash('success', 'Data tersimpan! Stok sinkron dengan PO Pemasok.');
    }

    public function toggleStatus($id)
    {
        $product = MerchantProduct::where('merchant_id', Auth::id())->findOrFail($id);
        $product->update(['is_tersedia' => !$product->is_tersedia]);
    }

    public function delete($id)
    {
        $product = MerchantProduct::where('merchant_id', Auth::id())->findOrFail($id);
        if ($product->foto_produk && Storage::disk('public')->exists($product->foto_produk)) {
            Storage::disk('public')->delete($product->foto_produk);
        }
        $product->delete();
        session()->flash('success', 'Menu berhasil dihapus.');
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative max-w-7xl mx-auto">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-2">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Katalog Menu & Etalase Kasir</h2>
            <p class="text-gray-500 text-sm mt-1 font-medium">Olah kiriman produk logistik Pemasok menjadi menu aktif di sistem kasir penjualan Anda.</p>
        </div>
        <button wire:click="openModalManual" class="px-5 py-2.5 bg-gray-900 text-white font-black text-sm rounded-xl transition shadow-lg flex items-center gap-2 hover:bg-black">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Buat Menu F&B Baru
        </button>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-indigo-50/50 border border-indigo-100 rounded-[24px] p-6 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 border-b border-indigo-100 pb-4">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-black">📦</span>
                <h3 class="font-black text-indigo-900 text-lg">Inbound Logistik (Daftar Pengiriman PO)</h3>
            </div>
            
            <div class="flex gap-2 bg-indigo-100/60 p-1 rounded-xl shrink-0">
                <button wire:click="$set('tabGudang', 'tersedia')" class="px-4 py-1.5 text-xs font-black rounded-lg transition-all {{ $tabGudang == 'tersedia' ? 'bg-white text-indigo-700 shadow-sm' : 'text-indigo-500 hover:text-indigo-700' }}">
                    📥 Menunggu Dimasukkan ({{ $this->poTersedia->count() }})
                </button>
                <button wire:click="$set('tabGudang', 'habis')" class="px-4 py-1.5 text-xs font-black rounded-lg transition-all {{ $tabGudang == 'habis' ? 'bg-white text-indigo-700 shadow-sm' : 'text-indigo-500 hover:text-indigo-700' }}">
                    ✅ Sudah di Kasir (Selesai)
                </button>
            </div>
        </div>
        
        <div class="flex overflow-x-auto gap-5 pb-4 scrollbar-hide">
            @if($tabGudang == 'tersedia')
                @forelse($this->poTersedia as $gudang)
                    <div class="min-w-[280px] max-w-[280px] bg-white border border-indigo-100 rounded-2xl p-4 shadow-sm flex flex-col justify-between hover:border-indigo-300 transition-colors">
                        <div class="space-y-3">
                            <div class="flex justify-between items-start">
                                <span class="text-[9px] font-black text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded font-mono shadow-inner">{{ $gudang->supplyOrder->nomor_order }}</span>
                                <span class="text-[9px] font-bold text-gray-400">{{ \Carbon\Carbon::parse($gudang->supplyOrder->updated_at)->format('H:i Wib') }}</span>
                            </div>

                            <div class="flex gap-3 items-center">
                                <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden shrink-0 shadow-sm">
                                    @if($gudang->produkPemasok && $gudang->produkPemasok->foto_produk)
                                        <img src="{{ asset('storage/' . $gudang->produkPemasok->foto_produk) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-xl bg-gray-100">🍲</div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-black text-gray-800 text-sm truncate leading-tight" title="{{ $gudang->nama_produk_snapshot }}">{{ $gudang->nama_produk_snapshot }}</h4>
                                    <p class="text-[11px] text-gray-500 font-bold mt-0.5">Kuantitas: <span class="text-indigo-600 font-black">{{ $gudang->qty }}</span> Unit</p>
                                </div>
                            </div>

                            <div class="bg-gray-50 border border-gray-100 p-2 rounded-xl space-y-1 text-[10px] font-medium text-gray-600">
                                <p class="truncate"><span class="font-bold text-gray-400">Pemasok:</span> {{ $gudang->supplyOrder->pemasok->pemasokProfile->nama_perusahaan ?? $gudang->supplyOrder->pemasok->name ?? 'Pemasok Umum' }}</p>
                                <p><span class="font-bold text-gray-400">Diterima:</span> {{ \Carbon\Carbon::parse($gudang->supplyOrder->updated_at)->format('d M Y') }}</p>
                            </div>
                        </div>
                        
                        <button wire:click="jadikanMenu({{ $gudang->id }})" class="mt-4 w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl transition shadow-md shadow-indigo-200/50">
                            + Pindahkan ke Kasir POS
                        </button>
                    </div>
                @empty
                    <div class="w-full py-8 text-center text-indigo-400 font-bold text-sm">Gudang kosong! Belum ada kiriman PO baru yang menunggu diproses.</div>
                @endforelse
            @else
                @forelse($this->poHabis as $gudang)
                    <div class="min-w-[280px] max-w-[280px] bg-gray-50/70 border border-gray-200 rounded-2xl p-4 flex flex-col justify-between opacity-65">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 font-mono shadow-inner">{{ $gudang->supplyOrder->nomor_order }}</span>
                                <span class="text-[10px] font-bold text-emerald-600 flex items-center gap-0.5">✓ Masuk POS</span>
                            </div>
                            <h4 class="font-bold text-gray-600 text-sm truncate line-through">{{ $gudang->nama_produk_snapshot }}</h4>
                            
                            <div class="bg-white/50 border border-gray-200 p-2 rounded-xl space-y-0.5 text-[9px] font-bold text-gray-500">
                                <p class="truncate"><span class="text-gray-400">Dari:</span> {{ $gudang->supplyOrder->pemasok->pemasokProfile->nama_perusahaan ?? $gudang->supplyOrder->pemasok->name ?? '-' }}</p>
                                <p><span class="text-gray-400">Selesai:</span> {{ \Carbon\Carbon::parse($gudang->supplyOrder->updated_at)->format('d M Y - H:i') }}</p>
                            </div>
                        </div>
                        <button disabled class="mt-4 w-full py-2 bg-gray-200 text-gray-400 font-bold text-xs rounded-xl cursor-not-allowed">
                            Sudah Dipindahkan
                        </button>
                    </div>
                @empty
                    <div class="w-full py-8 text-center text-gray-400 font-bold text-sm">Belum ada riwayat pemindahan barang PO di aplikasi ini.</div>
                @endforelse
            @endif
        </div>
    </div>

    <div class="flex items-center gap-2 mb-4 mt-8 border-b border-gray-200 pb-3">
        <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-black">🛒</span>
        <h3 class="font-black text-gray-900 text-lg">Etalase Layar Kasir (POS)</h3>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama menu jualan..." 
                class="w-full py-3 pl-11 pr-4 text-sm font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-200 transition">
        </div>
        <div class="flex gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
            @foreach(['semua' => 'Semua', 'makanan' => 'Makanan', 'minuman' => 'Minuman', 'barang_koperasi' => 'Koperasi', 'lainnya' => 'Lainnya'] as $val => $label)
                <button wire:click="$set('filterKategori', '{{ $val }}')" 
                    class="px-4 py-2.5 text-xs font-black rounded-xl whitespace-nowrap transition-colors border 
                    {{ $filterKategori === $val ? 'bg-emerald-50 border-emerald-200 text-emerald-700 shadow-sm' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
        @forelse($this->products as $item)
        <div class="bg-white rounded-[20px] border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden flex flex-col relative">
            <div class="relative h-40 w-full bg-gray-50 overflow-hidden">
                @if($item->foto_produk)
                    <img src="{{ asset('storage/' . $item->foto_produk) }}" alt="{{ $item->nama_produk }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 {{ !$item->is_tersedia ? 'grayscale opacity-60' : '' }}">
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-300 bg-gray-100 {{ !$item->is_tersedia ? 'opacity-50' : '' }}">
                        <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span class="text-[10px] font-bold">Belum Ada Foto</span>
                    </div>
                @endif

                <div class="absolute top-2 left-2 flex flex-col gap-1">
                    <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-1 rounded shadow-sm backdrop-blur-md
                        {{ $item->kategori == 'makanan' ? 'bg-orange-500/90 text-white' : ($item->kategori == 'minuman' ? 'bg-blue-500/90 text-white' : 'bg-purple-500/90 text-white') }}">
                        {{ str_replace('_', ' ', $item->kategori) }}
                    </span>
                    <span class="text-[10px] font-black px-2 py-1 rounded shadow-sm backdrop-blur-md bg-white/90 text-gray-800 border border-gray-200">
                        Stok: {{ $item->stok ?? 0 }}
                    </span>
                </div>
                <div class="absolute top-2 right-2">
                    <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex items-center h-6 rounded-full w-10 transition-colors shadow-sm focus:outline-none border-2 border-white {{ $item->is_tersedia ? 'bg-emerald-500' : 'bg-rose-500' }}">
                        <span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform {{ $item->is_tersedia ? 'translate-x-5' : 'translate-x-1' }}"></span>
                    </button>
                </div>
            </div>

            <div class="p-4 flex-1 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-black text-gray-900 leading-tight mb-3 {{ !$item->is_tersedia ? 'opacity-50 line-through' : '' }}">{{ $item->nama_produk }}</h3>
                </div>
                
                <div class="space-y-2 bg-gray-50 p-2 rounded-xl border border-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] text-gray-500 font-extrabold uppercase">Modal / Porsi</span>
                        <span class="text-[11px] text-gray-700 font-black">Rp{{ number_format($item->harga_pokok, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] text-emerald-600 font-extrabold uppercase">Harga Tunai</span>
                        <span class="text-xs text-emerald-600 font-black">Rp{{ number_format($item->harga_jual, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            
            <div class="absolute bottom-0 left-0 w-full px-4 py-3 bg-white/95 backdrop-blur-sm border-t border-gray-100 flex justify-between gap-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                <button wire:click="openModalEdit({{ $item->id }})" class="text-xs font-black text-blue-600 hover:text-white transition bg-blue-50 hover:bg-blue-600 px-3 py-2 rounded-xl w-full text-center border border-blue-100">Edit / Hias</button>
                <button wire:click="delete({{ $item->id }})" wire:confirm="Yakin ingin menghapus produk ini dari etalase?" class="text-xs font-black text-rose-500 hover:text-white transition bg-rose-50 hover:bg-rose-600 px-3 py-2 rounded-xl w-full text-center border border-rose-100">Hapus</button>
            </div>
        </div>
        @empty
        <div class="col-span-full py-24 text-center border border-gray-200 rounded-[30px] bg-white shadow-sm">
            <div class="text-5xl mb-4 opacity-30">🛒</div>
            <h3 class="text-xl font-black text-gray-900 mb-1">Etalase Masih Kosong</h3>
            <p class="text-gray-500 text-sm font-medium">Klik tombol ungu "+ Pindahkan ke Kasir" di atas untuk memasukkan barang jadi.</p>
        </div>
        @endforelse
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[24px] w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 flex-shrink-0">
                <h3 class="font-black text-gray-900 flex items-center gap-2 text-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    {{ $editId ? 'Atur Ulang Menu Etalase' : 'Konfirmasi Masuk Etalase Kasir' }}
                </h3>
                <button wire:click="closeModal" class="text-gray-400 hover:text-rose-500 bg-white border border-gray-200 p-1.5 rounded-full hover:bg-rose-50 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="save" class="overflow-y-auto flex-1 p-6 space-y-6">
                
                @if(session()->has('error_stok'))
                    <div class="bg-rose-50 border border-rose-200 text-rose-700 p-3 rounded-xl text-xs font-bold animate-shake">
                        {{ session('error_stok') }}
                    </div>
                @endif

                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                    <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3">Foto Menu (Tampil di Layar Kasir)</label>
                    <div class="flex items-center gap-5">
                        <div class="h-20 w-20 rounded-2xl bg-white border-2 border-dashed border-gray-300 overflow-hidden flex items-center justify-center flex-shrink-0 shadow-sm">
                            @if($foto_produk_baru)
                                <img src="{{ $foto_produk_baru->temporaryUrl() }}" class="w-full h-full object-cover">
                            @elseif($existing_foto)
                                <img src="{{ asset('storage/' . $existing_foto) }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input wire:model="foto_produk_baru" type="file" accept="image/jpeg,image/png,image/jpg,image/webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 cursor-pointer transition">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-2">Kategori Etalase POS</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach(['makanan' => '🍔 Makanan', 'minuman' => '🥤 Minuman', 'barang_koperasi' => '👕 Koperasi', 'lainnya' => '📦 Lainnya'] as $val => $label)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="kategori" value="{{ $val }}" class="peer sr-only">
                            <div class="text-center px-2 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-500 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition shadow-sm">{{ $label }}</div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Nama Makanan di Kasir</label>
                        <input wire:model="nama_produk" type="text" placeholder="Contoh: Nasi Goreng Ayam"
                            class="w-full py-3 px-4 text-sm font-black text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 transition">
                        @error('nama_produk') <span class="text-rose-500 text-[10px] mt-1 font-bold block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="sm:col-span-1">
                        <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5 flex justify-between items-center">
                            <span>Jml / Porsi</span>
                            @if($max_stok !== null)
                                <span class="text-indigo-600 font-extrabold text-[8px] bg-indigo-50 px-1 rounded border border-indigo-200">Max: {{ $max_stok }}</span>
                            @endif
                        </label>
                        <div class="relative">
                            <input wire:model.live="stok" type="number" min="0" {{ $max_stok !== null ? 'max='.$max_stok : '' }} placeholder="0"
                                class="w-full py-3 pl-4 pr-12 text-sm font-black text-center text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 transition">
                            @if($max_stok !== null)
                                <button type="button" wire:click="$set('stok', {{ $max_stok }})" class="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-100 text-indigo-700 text-[9px] font-black px-2 py-1.5 rounded-lg hover:bg-indigo-200 transition">
                                    MAX
                                </button>
                            @endif
                        </div>
                        @if($editId && !$processing_po_detail_id)
                            <p class="text-[8.5px] text-gray-400 mt-1 leading-tight font-bold">⚠️ Penambahan stok HANYA BISA melalui Inbound PO Gudang.</p>
                        @endif
                        @error('stok') <span class="text-rose-500 text-[10px] mt-1 font-bold block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 bg-blue-50/50 p-5 rounded-2xl border border-blue-100">
                    <div>
                        <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1.5">Harga Modal (Pokok)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-blue-500 font-bold text-xs">Rp</span>
                            <input wire:model.live.debounce.500ms="harga_pokok" type="number" step="100" 
                                class="w-full py-2.5 pl-10 pr-3 text-sm font-black text-blue-900 bg-white border border-blue-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1.5">Harga Jual (Tunai)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-emerald-500 font-bold text-xs">Rp</span>
                            <input wire:model.live.debounce.500ms="harga_jual" type="number" step="500" 
                                class="w-full py-2.5 pl-10 pr-3 text-sm font-black text-emerald-900 bg-white border border-emerald-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 transition shadow-sm">
                        </div>
                        @error('harga_jual') <span class="text-rose-500 text-[9px] mt-1 font-bold block">{{ $message }}</span> @enderror
                    </div>
                    
                    @if((int)$harga_jual > 0 && (int)$harga_pokok >= 0 && ((int)$harga_jual >= (int)$harga_pokok))
                    <div class="col-span-1 sm:col-span-2 pt-4 border-t border-blue-100 flex justify-between items-center">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Keuntungan Fisik Anda:</span>
                        <span class="text-sm font-black text-emerald-600 bg-emerald-100 px-3 py-1.5 rounded-lg border border-emerald-200">
                            + Rp {{ number_format((int)$harga_jual - (int)$harga_pokok, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif
                </div>

            </form>
            
            <div class="px-6 py-4 border-t border-gray-100 bg-white flex justify-end gap-3 flex-shrink-0">
                <button type="button" wire:click="closeModal" class="px-6 py-3 text-sm font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">Batal</button>
                <button wire:click="save" wire:loading.attr="disabled" class="px-6 py-3 text-sm font-black text-white bg-[#10b981] rounded-xl hover:bg-[#059669] transition shadow-lg shadow-emerald-200 flex items-center gap-2">
                    <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                    <span wire:loading wire:target="save">Memproses...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>