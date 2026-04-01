<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProduksiPemasok;
use Illuminate\Support\Facades\Auth;

class RiwayatProduksi extends Component
{
    use WithPagination;

    public $search = '';
    
    // Properti untuk Modal
    public bool $isModalOpen = false;
    public array $selectedItem = [];

    // Reset halaman ke 1 kalau user mengetik pencarian
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Fungsi untuk membuka modal dan menyiapkan data detail
    public function openModal($id)
    {
        // Cari data berdasarkan ID tabel (bukan kode batch)
        $produksi = ProduksiPemasok::find($id);
        
        if ($produksi) {
            // Kita mapping datanya ke bentuk array agar sesuai dengan desain Modal Anda
            $this->selectedItem = [
                'id' => $produksi->kode_batch,
                'item' => $produksi->nama_produk,
                'jumlah' => $produksi->jumlah,
                'satuan' => $produksi->satuan,
                'tanggal' => $produksi->waktu_produksi->format('d M Y'),
                'waktu' => $produksi->waktu_produksi->format('H:i'),
                'pic' => $produksi->penanggung_jawab,
                
                // Data Bahan Baku sementara dummy dulu karena belum ada relasi tabel detailnya
                'bahan_baku' => [
                    ['nama' => 'Bahan Mentah ' . $produksi->nama_produk, 'qty' => $produksi->jumlah, 'satuan' => $produksi->satuan],
                    ['nama' => 'Kemasan Plastik/Karung', 'qty' => $produksi->jumlah, 'satuan' => 'Pcs'],
                    ['nama' => 'Stiker Label QC', 'qty' => $produksi->jumlah, 'satuan' => 'Lembar'],
                ]
            ];
            
            $this->isModalOpen = true;
        }
    }

    // Fungsi untuk menutup modal
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedItem = [];
    }

    public function render()
    {
        $userId = Auth::id();

        // Menghitung Data Statistik Langsung dari Database
        $stats = [
            'total' => ProduksiPemasok::where('user_id', $userId)->count(),
            'sukses' => ProduksiPemasok::where('user_id', $userId)->where('status', 'selesai')->count(),
            'kendala' => ProduksiPemasok::where('user_id', $userId)->where('status', 'gagal')->count()
        ];

        // Mengambil Data Tabel dengan Pencarian & Pagination
        $riwayat = ProduksiPemasok::when($this->search, function ($query) {
        $query->where('kode_batch', 'like', '%' . $this->search . '%')
              ->orWhere('nama_produk', 'like', '%' . $this->search . '%');
        })
        ->orderBy('waktu_produksi', 'desc')
        ->paginate(10);// Tampilkan 10 data per halaman

        return view('livewire.pemasok.riwayat-produksi', [
            'stats' => $stats,
            'riwayat' => $riwayat
        ])->layout('layouts.app'); 
    }
}