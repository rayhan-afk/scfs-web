<?php

namespace App\Livewire\Merchant;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SupplyOrder;
use App\Models\PengajuanReturn;
use Illuminate\Support\Facades\Auth;

class FormReturn extends Component
{
    use WithFileUploads;

    public $orderId;
    public $alasan = '';
    public $deskripsi_masalah = '';
    public $foto_bukti;
    public $solusi_diajukan = 'refund';

    public function mount($orderId)
    {
        $this->orderId = $orderId;
    }

    public function simpanReturn()
    {
        $this->validate([
            'alasan' => 'required|string',
            'deskripsi_masalah' => 'required|string|min:10',
            'foto_bukti' => 'required|image|max:2048', // Maksimal 2MB
            'solusi_diajukan' => 'required|in:refund,kirim_ulang',
        ]);

        $order = SupplyOrder::findOrFail($this->orderId);

        // Ambil path foto setelah upload
        $pathFoto = $this->foto_bukti->store('foto_return', 'public');

        PengajuanReturn::create([
            'supply_order_id' => $order->id,
            'merchant_id' => Auth::id(),
            'supplier_id' => $order->supplier_id, // Diambil otomatis dari relasi order
            'alasan' => $this->alasan,
            'deskripsi_masalah' => $this->deskripsi_masalah,
            'foto_bukti' => $pathFoto,
            'solusi_diajukan' => $this->solusi_diajukan,
            'status' => 'pending',
        ]);

        session()->flash('message', 'Komplain return berhasil dikirim ke Pemasok.');
        return redirect()->route('merchant.riwayat');
    }

    public function ajukanBanding($returnId)
    {
        $return = PengajuanReturn::where('id', $returnId)->where('merchant_id', Auth::id())->firstOrFail();
        
        if ($return->status === 'ditolak') {
            $return->update(['status' => 'banding_lkbb']);
            session()->flash('message', 'Banding berhasil diajukan. LKBB akan meninjau sengketa ini.');
        }
    }

    public function render()
    {
        return view('livewire.merchant.form-return', [
            'order' => SupplyOrder::findOrFail($this->orderId),
            'riwayat_returns' => PengajuanReturn::where('merchant_id', Auth::id())->latest()->get()
        ])->layout('layouts.app');
    }
}