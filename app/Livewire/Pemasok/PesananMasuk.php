<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SupplyOrder;
use Illuminate\Support\Facades\Auth;

class PesananMasuk extends Component
{
    use WithPagination;

    // Default tab menggunakan status awal dari database
    public $activeTab = 'menunggu_lkbb'; 
    public $search = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updateStatusPesanan($id, $statusBaru)
{
    // Load pesanan beserta detail dan produk pemasoknya
    $order = SupplyOrder::with('details.produkPemasok')->find($id);
    
    if ($order) {
        // LOGIKA PENGURANGAN STOK
        // Stok dikurangi hanya saat status berubah dari 'menunggu_lkbb' ke 'diproses_pemasok'
        if ($statusBaru === 'diproses_pemasok' && $order->status === 'menunggu_lkbb') {
            foreach ($order->details as $detail) {
                // Pastikan produk terkait ditemukan
                if ($detail->produkPemasok) {
                    // Gunakan nama kolom 'stok_sekarang' sesuai isi model Anda
                    $detail->produkPemasok->decrement('stok_sekarang', $detail->qty);
                }
            }
        }

        // Update status di database
        $order->update(['status' => $statusBaru]);
        
        // Pesan notifikasi
        $pesanStatus = $statusBaru == 'diproses_pemasok' ? 'Diproses' : ($statusBaru == 'dikirim' ? 'Dikirim' : 'Selesai');
        session()->flash('message', "Status pesanan #{$order->nomor_order} berhasil diubah menjadi: {$pesanStatus}. Stok produk telah dikurangi otomatis.");
    }
}

    public function render()
    {
        $pesanan = SupplyOrder::with(['details.produkPemasok', 'merchant'])
            ->where('pemasok_id', Auth::id()) // <-- Ambil langsung dari kolom pemasok_id, tidak perlu whereHas yang berat
            ->when($this->activeTab === 'diproses_pemasok', function($query) {
                $query->whereIn('status', ['diproses_pemasok', 'dikirim']);
            }, function($query) {
                $query->where('status', $this->activeTab);
            })
            ->when($this->search, function ($query) {
                $query->where('nomor_order', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.pemasok.pesanan-masuk', [
            'daftarPesanan' => $pesanan
        ])->layout('layouts.app'); 
    }
}