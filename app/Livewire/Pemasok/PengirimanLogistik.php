<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;

class PengirimanLogistik extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'perlu_dikirim'; 
    
    // Modal State
    public $showModalAtur = false;
    public $showModalLacak = false;
    public $showModalUpdate = false; // Modal baru untuk update tracking
    public $showModalCetak = false;  // Modal baru untuk cetak resi
    public $selectedPesanan = null;

    // Form Atur Pengiriman
    public $kurir = '';
    public $no_resi = '';

    // Form Update Tracking
    public $newTrackingStatus = '';

    // Gunakan array public agar data bisa dimanipulasi/disimpan sementara selama halaman tidak di-refresh
    public array $dataPengiriman = [];

    public function mount()
    {
        // Pindahkan data ke mount() agar state-nya bertahan saat dimodifikasi
        $this->dataPengiriman = [
            [
                'id' => 'ORD-1092',
                'pembeli' => 'Toko Kelontong Berkah',
                'alamat' => 'Jl. Merdeka No. 45, Bandung',
                'item' => '5x Beras Premium, 2x Minyak Goreng',
                'status' => 'perlu_dikirim',
                'tanggal_pesan' => '05 Mar 2026, 08:30 WIB'
            ],
            [
                'id' => 'ORD-1090',
                'pembeli' => 'Warung Makmur',
                'alamat' => 'Jl. Sudirman No. 12, Bandung',
                'item' => '10x Gula Pasir 1kg',
                'status' => 'sedang_dikirim',
                'kurir' => 'Kurir Internal SCFS (Bpk. Yanto)',
                'no_resi' => 'SCFS-BDG-00192',
                'estimasi' => '05 Mar 2026',
                'pelacakan' => [
                    ['waktu' => '05 Mar 2026 09:00', 'status' => 'Pesanan dibawa oleh kurir menuju lokasi pembeli.', 'aktif' => true],
                    ['waktu' => '05 Mar 2026 07:30', 'status' => 'Pesanan telah diserahkan ke pihak logistik.', 'aktif' => false],
                ]
            ],
            [
                'id' => 'ORD-1085',
                'pembeli' => 'Toko Sembako Maju',
                'alamat' => 'Jl. Pahlawan No. 8, Bandung',
                'item' => '20x Beras Premium 5kg',
                'status' => 'riwayat',
                'kurir' => 'Lalamove (B-1234-XYZ)',
                'no_resi' => 'LLM-998877',
                'waktu_sampai' => '04 Mar 2026 14:20 WIB',
                'pelacakan' => [
                    ['waktu' => '04 Mar 2026 14:20', 'status' => 'Paket telah diterima oleh Bpk. Andi (Pemilik).', 'aktif' => true],
                    ['waktu' => '04 Mar 2026 13:00', 'status' => 'Pesanan dibawa oleh kurir menuju lokasi pembeli.', 'aktif' => false],
                ]
            ],
        ];
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // Modal Atur Pengiriman
    public function bukaModalAtur($id)
    {
        $this->selectedPesanan = collect($this->dataPengiriman)->firstWhere('id', $id);
        $this->no_resi = 'SCFS-' . rand(10000, 99999);
        $this->kurir = '';
        $this->showModalAtur = true;
    }

    // Modal Lacak Status
    public function bukaModalLacak($id)
    {
        $this->selectedPesanan = collect($this->dataPengiriman)->firstWhere('id', $id);
        $this->showModalLacak = true;
    }

    // Modal Cetak Label
    public function cetakLabel($id)
    {
        $this->selectedPesanan = collect($this->dataPengiriman)->firstWhere('id', $id);
        // Jika belum ada resi (karena masih Perlu Dikirim), buat dummy
        if(!isset($this->selectedPesanan['no_resi'])) {
            $this->selectedPesanan['no_resi'] = 'DRAFT-' . rand(1000,9999);
            $this->selectedPesanan['kurir'] = 'Belum Diatur';
        }
        $this->showModalCetak = true;
    }

    // Modal Update Tracking
    public function bukaModalUpdate($id)
    {
        $this->selectedPesanan = collect($this->dataPengiriman)->firstWhere('id', $id);
        $this->newTrackingStatus = '';
        $this->showModalUpdate = true;
    }

    public function simpanPengiriman()
    {
        $this->validate(['kurir' => 'required', 'no_resi' => 'required']);
        $this->showModalAtur = false;
        session()->flash('message', 'Pengiriman berhasil diatur! Status berubah menjadi Sedang Dikirim.');
    }

    // Menyimpan Status Tracking Baru
    public function simpanTracking()
    {
        $this->validate([
            'newTrackingStatus' => 'required|min:5'
        ], [
            'newTrackingStatus.required' => 'Status pelacakan tidak boleh kosong.',
            'newTrackingStatus.min' => 'Status pelacakan minimal 5 karakter.'
        ]);

        // Cari index data pesanan
        $index = collect($this->dataPengiriman)->search(fn($item) => $item['id'] === $this->selectedPesanan['id']);
        
        if ($index !== false) {
            // Matikan status 'aktif' pada pelacakan lama
            foreach ($this->dataPengiriman[$index]['pelacakan'] as &$track) {
                $track['aktif'] = false;
            }

            // Tambahkan status baru di urutan paling atas (awal array)
            array_unshift($this->dataPengiriman[$index]['pelacakan'], [
                'waktu' => now()->format('d M Y H:i') . ' WIB',
                'status' => $this->newTrackingStatus,
                'aktif' => true
            ]);

            $this->showModalUpdate = false;
            $this->newTrackingStatus = '';
            session()->flash('message', 'Status tracking berhasil di-update!');
        }
    }

    public function render()
    {
        $data = collect($this->dataPengiriman)->filter(function ($item) {
            $matchTab = $item['status'] === $this->activeTab;
            $matchSearch = empty($this->search) || 
                           str_contains(strtolower($item['id']), strtolower($this->search)) || 
                           str_contains(strtolower($item['pembeli']), strtolower($this->search));
            return $matchTab && $matchSearch;
        });

        return view('livewire.pemasok.pengiriman-logistik', [
            'pengiriman' => $data
        ])->layout('layouts.app');
    }
}