<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;

class RiwayatProduksi extends Component
{
    use WithPagination;

    public $search = '';
    
    // Properti untuk Modal
    public bool $isModalOpen = false;
    public array $selectedItem = [];

    // Data Statistik Dummy
    public $stats = [
        'total' => 142,
        'sukses' => 138,
        'kendala' => 4
    ];

    // Method pembantu untuk mengambil data (agar bisa dipakai berulang)
    private function getAllData()
    {
        return collect([
            [
                'id' => 'PRD-20240301-01',
                'item' => 'Beras Premium 5kg',
                'jumlah' => 150,
                'satuan' => 'Karung',
                'tanggal' => '01 Mar 2026',
                'waktu' => '08:00',
                'pic' => 'Ahmad Budi',
                'status' => 'Selesai',
                'status_color' => 'green'
            ],
            [
                'id' => 'PRD-20240301-02',
                'item' => 'Minyak Goreng 2L',
                'jumlah' => 80,
                'satuan' => 'Pouch',
                'tanggal' => '01 Mar 2026',
                'waktu' => '10:30',
                'pic' => 'Siti Aminah',
                'status' => 'Selesai',
                'status_color' => 'green'
            ],
            [
                'id' => 'PRD-20240228-05',
                'item' => 'Gula Pasir 1kg',
                'jumlah' => 200,
                'satuan' => 'Pack',
                'tanggal' => '28 Feb 2026',
                'waktu' => '14:00',
                'pic' => 'Budi Santoso',
                'status' => 'Gagal/Kendala',
                'status_color' => 'red'
            ],
            [
                'id' => 'PRD-20240228-04',
                'item' => 'Tepung Terigu',
                'jumlah' => 50,
                'satuan' => 'Karung',
                'tanggal' => '28 Feb 2026',
                'waktu' => '16:00',
                'pic' => 'Ahmad Budi',
                'status' => 'Selesai',
                'status_color' => 'green'
            ],
        ]);
    }

    // Fungsi untuk membuka modal dan menyiapkan data detail
    public function openModal($id)
    {
        $item = $this->getAllData()->firstWhere('id', $id);
        
        if ($item) {
            // Kita tambahkan data "Bahan Baku" secara dinamis sebagai dummy detail
            $item['bahan_baku'] = [
                ['nama' => 'Bahan Mentah ' . $item['item'], 'qty' => $item['jumlah'], 'satuan' => $item['satuan']],
                ['nama' => 'Kemasan Plastik/Karung', 'qty' => $item['jumlah'], 'satuan' => 'Pcs'],
                ['nama' => 'Stiker Label QC', 'qty' => $item['jumlah'], 'satuan' => 'Lembar'],
            ];
            
            $this->selectedItem = $item;
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
        $riwayat = $this->getAllData()->filter(function ($item) {
            return empty($this->search) || 
                   str_contains(strtolower($item['item']), strtolower($this->search)) ||
                   str_contains(strtolower($item['id']), strtolower($this->search));
        });

        return view('livewire.pemasok.riwayat-produksi', [
            'riwayat' => $riwayat
        ])->layout('layouts.app'); // Sesuaikan dengan nama layout Anda
    }
}