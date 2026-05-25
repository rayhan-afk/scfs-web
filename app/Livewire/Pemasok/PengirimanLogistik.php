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
    public $nama_kurir = '';
    public $no_hp_kurir = '';
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
        $this->no_resi = 'SCFS-' . strtoupper(substr(uniqid(), -6));
        $this->nama_kurir = '';
        $this->no_hp_kurir = '';
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
            'nama_kurir' => 'required|string|max:100',
            'no_hp_kurir' => 'required|digits_between:10,15',
            'no_resi' => 'required',
        ]);

        $order = SupplyOrder::where('pemasok_id', Auth::id())->find($this->selectedOrderId);

        if ($order && $order->status === 'diproses_pemasok') {
            $order->update([
                'status' => 'dikirim',
                'nama_kurir' => $this->nama_kurir,
                'no_hp_kurir' => $this->no_hp_kurir,
                'no_resi' => $this->no_resi,
            ]);

            session()->flash('message', 'Pengiriman berhasil diatur! Pesanan sekarang SEDANG DIKIRIM.');
        }

        $this->showModalAtur = false;
        $this->reset(['nama_kurir', 'no_hp_kurir', 'no_resi', 'selectedOrderId']);
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