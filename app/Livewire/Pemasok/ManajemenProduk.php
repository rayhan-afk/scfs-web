<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\ProdukPemasok;
use App\Models\RiwayatOpnamePemasok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class ManajemenProduk extends Component
{
    use WithPagination, WithFileUploads;

    // State UI
    public $search = '';
    public $filterStatus = '';
    public $showModalProduk = false;
    public $showModalOpname = false;
    public $isEdit = false;

    // Data Form Produk
    public $produk_id, $sku, $nama_produk, $harga_grosir, $stok_sekarang, $batas_minimum_stok, $deskripsi, $foto_produk, $foto_produk_lama;

    // Data Form Opname
    public $stok_fisik, $keterangan_opname;

    public $filterKritis = false;

    public $satuan = 'pcs';

    public $kritis = false;

    public $action = '';

    protected $updatesQueryString = ['search'];

    public function mount()
    {
        // Jika dari dashboard diklik "Stok Menipis"
        if ($this->kritis) {
            $this->filterKritis = true; 
        }

        // Jika dari dashboard diklik "Tambah Produk"
        if ($this->action === 'tambah') {
            $this->bukaModalTambah();
            // Reset action agar tidak terus-terusan terbuka jika direfresh
            $this->action = ''; 
        }
    }
    public function render()
    {
        $query = ProdukPemasok::where('user_id', Auth::id())
            ->where('nama_produk', 'like', '%' . $this->search . '%');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        // Jika filter kritis aktif, hanya tampilkan yang butuh restock
        if ($this->filterKritis) {
            $query->whereColumn('stok_sekarang', '<=', 'batas_minimum_stok');
        }
        return view('livewire.pemasok.manajemen-produk', [
            'produks' => $query->latest()->paginate(10),
            'total_produk' => ProdukPemasok::where('user_id', Auth::id())->count(),
            'stok_menipis' => ProdukPemasok::where('user_id', Auth::id())
                ->whereColumn('stok_sekarang', '<=', 'batas_minimum_stok')->count()
        ])->layout('layouts.app');
    }

    // Modal Handlers
    public function bukaModalTambah()
    {
       // Ini kunci untuk memperbaiki bug foto lama yang nyangkut
        $this->reset([
            'nama_produk', 'harga_grosir', 'stok_sekarang', 
            'batas_minimum_stok', 'deskripsi', 'foto_produk', 
            'foto_produk_lama', 'satuan' // pastikan satuan juga di-reset
        ]);

        $this->isEdit = false;
        $this->sku = 'SKU-' . strtoupper(\Illuminate\Support\Str::random(6));
        $this->satuan = 'pcs'; // Set default ke pcs
        $this->showModalProduk = true;
    }

    public function simpanProduk()
    {
        $this->validate([
            'sku' => 'required|unique:produk_pemasoks,sku,' . $this->produk_id,
            'nama_produk' => 'required|min:3',
            'harga_grosir' => 'required|numeric',
            'stok_sekarang' => 'required|integer',
            'batas_minimum_stok' => 'required|integer',
            'satuan' => 'required|string|max:20',
            'foto_produk' => $this->isEdit ? 'nullable|image|max:1024' : 'required|image|max:1024',
        ]);

        $data = [
            'user_id' => Auth::id(),
            'sku' => $this->sku,
            'nama_produk' => $this->nama_produk,
            'harga_grosir' => $this->harga_grosir,
            'stok_sekarang' => $this->stok_sekarang,
            'batas_minimum_stok' => $this->batas_minimum_stok,
            'deskripsi' => $this->deskripsi,
            'satuan' => $this->satuan,
        ];

        if ($this->foto_produk) {
            $data['foto_produk'] = $this->foto_produk->store('produk', 'public');
        }

        ProdukPemasok::updateOrCreate(['id' => $this->produk_id], $data);

        $this->dispatch('toast', ['message' => 'Produk berhasil disimpan!', 'type' => 'success']);
        $this->showModalProduk = false;
        $this->resetForm();
    }

    public function bukaModalOpname($id)
    {
        $this->produk_id = $id;
        $produk = ProdukPemasok::find($id);
        $this->nama_produk = $produk->nama_produk;
        $this->stok_sekarang = $produk->stok_sekarang;
        $this->showModalOpname = true;
    }

    public function simpanOpname()
    {
        $this->validate([
            'stok_fisik' => 'required|integer',
            'keterangan_opname' => 'nullable|string'
        ]);

        $produk = ProdukPemasok::find($this->produk_id);
        $selisih = $this->stok_fisik - $produk->stok_sekarang;

        RiwayatOpnamePemasok::create([
            'produk_pemasok_id' => $this->produk_id,
            'stok_sistem' => $produk->stok_sekarang,
            'stok_fisik' => $this->stok_fisik,
            'selisih' => $selisih,
            'keterangan' => $this->keterangan_opname
        ]);

        $produk->update(['stok_sekarang' => $this->stok_fisik]);

        $this->showModalOpname = false;
        $this->reset(['stok_fisik', 'keterangan_opname']);
        $this->dispatch('toast', ['message' => 'Stok Opname berhasil dicatat!', 'type' => 'success']);
    }
    public function updatedFotoProduk()
    {
        $this->validate(['foto_produk' => 'image|max:1024']);
    }
    public function editProduk($id)
    {
        $this->isEdit = true;
        $this->produk_id = $id;
        $produk = ProdukPemasok::findOrFail($id);
        
        $this->sku = $produk->sku;
        $this->nama_produk = $produk->nama_produk;
        $this->harga_grosir = $produk->harga_grosir;
        $this->stok_sekarang = $produk->stok_sekarang;
        $this->batas_minimum_stok = $produk->batas_minimum_stok;
        $this->deskripsi = $produk->deskripsi;
        $this->foto_produk_lama = $produk->foto_produk;
        $this->satuan = $produk->satuan ?? 'pcs';
        $this->showModalProduk = true;
    }
    

    private function resetForm()
    {
        $this->reset(['produk_id', 'sku', 'nama_produk', 'harga_grosir', 'stok_sekarang', 'batas_minimum_stok', 'deskripsi', 'foto_produk', 'isEdit']);
    }

    
}