<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SupplyOrder;
use Illuminate\Support\Facades\Auth;

class PesananMasuk extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        // Hanya mengambil pesanan yang baru masuk atau sedang diproses (belum dikirim/selesai)
        $pesanan = SupplyOrder::with(['details.produkPemasok'])
            ->where('pemasok_id', Auth::id())
            ->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'ditolak'])
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