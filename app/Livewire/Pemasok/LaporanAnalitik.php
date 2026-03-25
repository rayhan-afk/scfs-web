<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;

class LaporanAnalitik extends Component
{
    use WithPagination;

    public $activeTab = 'penjualan'; // Default tab
    public $periode = 'bulan_ini'; // filter: hari_ini, bulan_ini, tahun_ini

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage(); // Reset pagination saat ganti tab
    }

    public function downloadLaporan()
    {
        // Logika untuk export ke PDF/Excel (bisa menggunakan package seperti dompdf atau maatwebsite/excel nantinya)
        session()->flash('message', 'Fitur download laporan sedang dalam pengembangan.');
    }

    public function render()
    {
        // CATATAN: Ini adalah contoh query. Silakan sesuaikan dengan nama Model & Relasi Database Anda sebenarnya.
        
        // Data Tab 1: Penjualan
        $totalPendapatan = 25000000; // Ganti dengan query SUM total_harga pesanan selesai
        $totalPesanan = 125; // Ganti dengan query COUNT pesanan selesai

        // Data Tab 2: Pergerakan Stok (Contoh dummy array, ganti dengan query dari tabel riwayat_stok)
        $riwayatStok = [
            ['tanggal' => '2023-10-25 10:00', 'produk' => 'Beras Premium 5kg', 'jenis' => 'masuk', 'jumlah' => 50, 'keterangan' => 'Restock mingguan'],
            ['tanggal' => '2023-10-25 14:30', 'produk' => 'Beras Premium 5kg', 'jenis' => 'keluar', 'jumlah' => 15, 'keterangan' => 'Pesanan #INV-001'],
            ['tanggal' => '2023-10-24 09:15', 'produk' => 'Minyak Goreng 2L', 'jenis' => 'keluar', 'jumlah' => 10, 'keterangan' => 'Pesanan #INV-002'],
        ];

        return view('livewire.pemasok.laporan-analitik', [
            'totalPendapatan' => $totalPendapatan,
            'totalPesanan' => $totalPesanan,
            'riwayatStok' => $riwayatStok
        ])->layout('layouts.app'); // Sesuaikan dengan layout Anda
    }
}