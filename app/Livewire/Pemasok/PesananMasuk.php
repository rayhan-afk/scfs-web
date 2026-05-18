<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SupplyOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class PesananMasuk extends Component
{
    use WithPagination;

    public $search = '';

    // State untuk Modal
    public $showModalDetail = false;
    public $showModalTolak = false;
    public $selectedOrderId = null;
    public $alasanPenolakan = '';

    public function bukaModalDetail($id)
    {
        $this->selectedOrderId = $id;
        $this->showModalDetail = true;
    }

    public function tutupModal()
    {
        $this->showModalDetail = false;
        $this->showModalTolak = false;
        $this->reset(['selectedOrderId', 'alasanPenolakan']);
    }

    public function bukaModalTolak()
    {
        $this->showModalDetail = false;
        $this->showModalTolak = true;
    }

    // Aksi: Pemasok Menyetujui Pesanan
    public function setujuiPesanan()
    {
        $order = SupplyOrder::where('pemasok_id', Auth::id())->find($this->selectedOrderId);
        
        if ($order && $order->status === 'menunggu_pemasok') {
            // Ubah status agar diteruskan ke meja LKBB
            $order->update(['status' => 'menunggu_lkbb']);
            session()->flash('success', 'Pesanan disetujui! Telah diteruskan ke LKBB untuk proses pencairan dana.');
        }

        $this->tutupModal();
    }

    // Aksi: Pemasok Menolak Pesanan
    public function tolakPesanan()
    {
        $this->validate([
            'alasanPenolakan' => 'required|min:5'
        ]);

        $order = SupplyOrder::where('pemasok_id', Auth::id())->find($this->selectedOrderId);
        
        if ($order && $order->status === 'menunggu_pemasok') {
            $order->update([
                'status' => 'ditolak',
                'catatan' => 'Ditolak Pemasok: ' . $this->alasanPenolakan
            ]);
            session()->flash('error', 'Pesanan telah ditolak dan dibatalkan.');
        }

        $this->tutupModal();
    }

    #[Computed]
    public function selectedOrder()
    {
        if (!$this->selectedOrderId) return null;
        return SupplyOrder::with(['merchant.merchantProfile', 'details'])->find($this->selectedOrderId);
    }

    public function render()
    {
        // Pemasok memantau pesanan yang baru masuk, sedang di-review LKBB, atau sudah cair
        $pesanan = SupplyOrder::with(['details.produkPemasok', 'merchant.merchantProfile'])
            ->where('pemasok_id', Auth::id())
            ->whereIn('status', ['menunggu_pemasok', 'menunggu_lkbb', 'diproses_pemasok', 'ditolak'])
            ->when($this->search, function ($query) {
                $query->where('nomor_order', 'like', '%' . $this->search . '%')
                      ->orWhereHas('merchant', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('merchant.merchantProfile', function($q) {
                          $q->where('nama_kantin', 'like', '%' . $this->search . '%');
                      });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.pemasok.pesanan-masuk', [
            'daftarPesanan' => $pesanan
        ])->layout('layouts.app'); 
    }
}