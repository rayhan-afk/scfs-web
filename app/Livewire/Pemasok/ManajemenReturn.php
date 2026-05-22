<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use App\Models\PengajuanReturn;
use Illuminate\Support\Facades\Auth;

class ManajemenReturn extends Component
{
    public $catatan_pemasok = [];

    public function prosesSetuju($returnId)
    {
        $return = PengajuanReturn::where('id', $returnId)->where('supplier_id', Auth::id())->firstOrFail();
        
        $return->update([
            'status' => 'disetujui',
            'catatan_pemasok' => $this->catatan_pemasok[$returnId] ?? 'Disetujui oleh pemasok.'
        ]);

        session()->flash('message', 'Return berhasil disetujui.');
    }

    public function prosesTolak($returnId)
    {
        if (empty($this->catatan_pemasok[$returnId])) {
            session()->flash('error', 'Wajib mengisi alasan penolakan pada catatan!');
            return;
        }

        $return = PengajuanReturn::where('id', $returnId)->where('supplier_id', Auth::id())->firstOrFail();
        
        $return->update([
            'status' => 'ditolak',
            'catatan_pemasok' => $this->catatan_pemasok[$returnId]
        ]);

        session()->flash('message', 'Return ditolak. Menunggu keputusan lanjutan dari Merchant.');
    }

    public function render()
    {
        return view('livewire.pemasok.manajemen-return', [
            'returns' => PengajuanReturn::where('supplier_id', Auth::id())->with(['merchant', 'supplyOrder'])->latest()->get()
        ])->layout('layouts.app');
    }
}