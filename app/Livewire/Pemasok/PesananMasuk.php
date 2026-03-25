<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;

class PesananMasuk extends Component
{
    use WithPagination;

    public $activeTab = 'baru'; // Pilihan: baru, diproses, dikirim, selesai
    public $search = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updateStatusPesanan($id, $statusBaru)
    {
        // Nanti di sini logika update ke database (tabel transactions / supply_chains)
        session()->flash('message', "Status pesanan #$id berhasil diubah menjadi: $statusBaru");
    }

    public function render()
    {
        // Data Dummy Orderan dari Merchant (Nanti diganti dengan Query Database sungguhan)
        $pesanan = [
            [
                'id' => 'ORD-1092',
                'tanggal' => '2023-10-27 08:30',
                'merchant' => 'Toko Kelontong Berkah (Bpk. Budi)',
                'total_harga' => 1500000,
                'status' => 'baru',
                'item' => '5x Beras Premium 5kg, 2x Minyak Goreng 2L',
                'alamat' => 'Jl. Merdeka No. 45, Bandung'
            ],
            [
                'id' => 'ORD-1090',
                'tanggal' => '2023-10-26 14:15',
                'merchant' => 'Warung Makmur (Ibu Siti)',
                'total_harga' => 850000,
                'status' => 'diproses',
                'item' => '10x Gula Pasir 1kg, 5x Kopi Bubuk',
                'alamat' => 'Jl. Sudirman No. 12, Bandung'
            ],
            [
                'id' => 'ORD-1085',
                'tanggal' => '2023-10-25 09:00',
                'merchant' => 'Toko Sembako Maju (Bpk. Andi)',
                'total_harga' => 3200000,
                'status' => 'selesai',
                'item' => '20x Beras Premium 5kg, 10x Telur 1kg',
                'alamat' => 'Jl. Pahlawan No. 8, Bandung'
            ],
        ];

        // Filter data berdasarkan Tab yang aktif
        $filteredPesanan = collect($pesanan)->filter(function ($p) {
            return $p['status'] === $this->activeTab;
        });

        return view('livewire.pemasok.pesanan-masuk', [
            'daftarPesanan' => $filteredPesanan
        ])->layout('layouts.app'); // Sesuaikan layout jika Anda pakai nama lain
    }
}