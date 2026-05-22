<?php

namespace App\Livewire\Lkbb;

use Livewire\Component;
use App\Models\PengajuanReturn;

class MonitoringReturn extends Component
{
    public $catatan_lkbb = [];

    public function putuskanSengketa($returnId, $keputusanFinal)
    {
        if (empty($this->catatan_lkbb[$returnId])) {
            session()->flash('error', 'LKBB wajib menuliskan alasan keputusan hukum arbitrase!');
            return;
        }

        $return = PengajuanReturn::findOrFail($returnId);
        
        $return->update([
            'status' => 'selesai_lkbb',
            'catatan_lkbb' => 'Keputusan LKBB (' . strtoupper($keputusanFinal) . '): ' . $this->catatan_lkbb[$returnId]
        ]);

        session()->flash('message', 'Sengketa resmi ditutup dengan keputusan final.');
    }

    public function render()
    {
        return view('livewire.lkbb.monitoring-return', [
            'all_returns' => PengajuanReturn::with(['merchant', 'supplier'])->latest()->get()
        ])->layout('layouts.lkbb'); // Memakai layout lkbb khusus milikmu
    }
}