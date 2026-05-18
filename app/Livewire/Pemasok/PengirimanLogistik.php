<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SupplyOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class PengirimanLogistik extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'diproses_pemasok'; 
    
    // Modal States
    public $showModalAtur = false;
    public $showModalCetak = false; 
    public $showModalDetail = false; // <-- MODAL BARU UNTUK VIEW DETAIL
    public $selectedOrderId = null;

    // Form Atur Pengiriman
    public $kurir = '';
    public $no_resi = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function bukaModalDetail($id)
    {
        $this->selectedOrderId = $id;
        $this->showModalDetail = true;
    }

    public function bukaModalAtur($id)
    {
        $this->selectedOrderId = $id;
        $this->no_resi = 'SCFS-' . strtoupper(substr(uniqid(), -6)); // Generate resi otomatis
        $this->kurir = '';
        $this->showModalAtur = true;
    }

    public function cetakLabel($id)
    {
        $this->selectedOrderId = $id;
        $this->showModalCetak = true;
    }

    public function simpanPengiriman()
    {
        $this->validate([
            'kurir' => 'required', 
            'no_resi' => 'required'
        ]);

        $order = SupplyOrder::where('pemasok_id', Auth::id())->find($this->selectedOrderId);
        
        if ($order && $order->status === 'diproses_pemasok') {
            $infoPengiriman = "Dikirim via: " . $this->kurir . " | Resi: " . $this->no_resi;
            
            $order->update([
                'status' => 'dikirim',
                'catatan' => $order->catatan ? $order->catatan . "\n\n[UPDATE LOGISTIK]\n" . $infoPengiriman : "[UPDATE LOGISTIK]\n" . $infoPengiriman
            ]);

            session()->flash('message', 'Pengiriman berhasil diatur! Pesanan sekarang SEDANG DIKIRIM.');
        }

        $this->showModalAtur = false;
        $this->reset(['kurir', 'no_resi', 'selectedOrderId']);
    }

    #[Computed]
    public function selectedOrder()
    {
        if (!$this->selectedOrderId) return null;
        return SupplyOrder::with(['merchant.merchantProfile', 'details'])->find($this->selectedOrderId);
    }

    public function render()
    {
        $orders = SupplyOrder::with(['merchant.merchantProfile', 'details'])
            ->where('pemasok_id', Auth::id())
            ->where('status', $this->activeTab)
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

        $countPerluDikirim = SupplyOrder::where('pemasok_id', Auth::id())
            ->where('status', 'diproses_pemasok')
            ->count();

        return view('livewire.pemasok.pengiriman-logistik', [
            'orders' => $orders,
            'countPerluDikirim' => $countPerluDikirim
        ])->layout('layouts.app');
    }
}