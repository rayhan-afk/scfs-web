<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SupplyOrder; // Pastikan model ini sesuai dengan model pesanan Anda
use Illuminate\Support\Facades\Auth;

class PengirimanLogistik extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'perlu_dikirim'; 
    
    // Modal State
    public $showModalAtur = false;
    public $showModalLacak = false;
    public $showModalUpdate = false;
    public $showModalCetak = false; 
    public $selectedPesanan = null;

    // Form Atur Pengiriman
    public $kurir = '';
    public $no_resi = '';

    // Form Update Tracking
    public $newTrackingStatus = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function bukaModalAtur($id)
    {
        $this->selectedPesanan = $this->getPesananDetail($id);
        $this->no_resi = 'SCFS-' . strtoupper(uniqid()); // Generate resi sementara
        $this->kurir = '';
        $this->showModalAtur = true;
    }

    public function bukaModalLacak($id)
    {
        $this->selectedPesanan = $this->getPesananDetail($id);
        $this->showModalLacak = true;
    }

    public function cetakLabel($id)
    {
        $this->selectedPesanan = $this->getPesananDetail($id);
        
        if(!isset($this->selectedPesanan['no_resi']) || empty($this->selectedPesanan['no_resi'])) {
            $this->selectedPesanan['no_resi'] = 'DRAFT-' . rand(1000,9999);
            $this->selectedPesanan['kurir'] = 'Belum Diatur';
        }
        $this->showModalCetak = true;
    }

    public function bukaModalUpdate($id)
    {
        $this->selectedPesanan = $this->getPesananDetail($id);
        $this->newTrackingStatus = '';
        $this->showModalUpdate = true;
    }

    // Aksi Simpan Pengiriman
    public function simpanPengiriman()
    {
        $this->validate([
            'kurir' => 'required', 
            'no_resi' => 'required'
        ]);

        $order = SupplyOrder::find($this->selectedPesanan['id_asli']);
        
        if ($order) {
            $order->update([
                'status' => 'dikirim', // Disesuaikan dengan ENUM Anda
                'kurir' => $this->kurir,
                'no_resi' => $this->no_resi,
                'tracking_history' => json_encode([
                    [
                        'waktu' => now()->format('d M Y H:i'),
                        'status' => 'Pesanan telah diserahkan ke pihak logistik ('.$this->kurir.').',
                        'aktif' => true
                    ]
                ])
            ]);
        }

        $this->showModalAtur = false;
        session()->flash('message', 'Pengiriman berhasil diatur! Pesanan dipindah ke Sedang Dikirim.');
    }

    // Aksi Update Status Tracking
    public function simpanTracking()
    {
        $this->validate([
            'newTrackingStatus' => 'required|min:5'
        ]);

        $order = SupplyOrder::find($this->selectedPesanan['id_asli']);
        
        if ($order) {
            $historyLama = $order->tracking_history ? json_decode($order->tracking_history, true) : [];
            
            // Matikan status aktif sebelumnya
            foreach ($historyLama as &$track) {
                $track['aktif'] = false;
            }

            // Tambahkan status baru di urutan teratas
            array_unshift($historyLama, [
                'waktu' => now()->format('d M Y H:i'),
                'status' => $this->newTrackingStatus,
                'aktif' => true
            ]);

            $order->update([
                'tracking_history' => json_encode($historyLama)
            ]);

            $this->showModalUpdate = false;
            $this->newTrackingStatus = '';
            session()->flash('message', 'Status tracking berhasil di-update!');
        }
    }

    // Helper untuk mengambil 1 pesanan (Format Array UI)
    private function getPesananDetail($idStr)
    {
        $allData = $this->getDataDariDatabase();
        return collect($allData)->firstWhere('id', $idStr);
    }

    // Mengambil data real dari DB dan diubah jadi array yang cocok untuk UI Blade
    private function getDataDariDatabase()
    {
        // Sesuaikan relasi (misal details dan merchant) dengan nama relasi di Model Anda
        $orders = SupplyOrder::with(['details.produkPemasok', 'merchant']) 
            ->whereHas('details.produkPemasok', function($q) {
                $q->where('user_id', Auth::id());
            })
            // Gunakan status dari ENUM Anda
            ->whereIn('status', ['diproses_pemasok', 'dikirim', 'selesai', 'ditolak'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return $orders->map(function($order) {
            $items = [];
            foreach($order->details as $detail) {
                if($detail->produkPemasok && $detail->produkPemasok->user_id == Auth::id()) {
                    $items[] = $detail->qty . 'x ' . $detail->nama_bahan_snapshot;
                }
            }

            // Menentukan tab berdasarkan status database Anda
            $statusTab = 'perlu_dikirim';
            if ($order->status == 'dikirim') $statusTab = 'sedang_dikirim';
            if (in_array($order->status, ['selesai', 'ditolak'])) $statusTab = 'riwayat';

            return [
                'id_asli' => $order->id,
                'id' => $order->nomor_order ?? 'ORD-'.$order->id,
                'pembeli' => $order->merchant->nama_toko ?? 'Merchant ID: '.$order->merchant_id,
                'alamat' => $order->merchant->alamat ?? 'Alamat tidak tersedia',
                'item' => implode(', ', $items),
                'status' => $statusTab,
                'kurir' => $order->kurir ?? '-',
                'no_resi' => $order->no_resi ?? '-',
                'waktu_sampai' => $order->updated_at->format('d M Y H:i WIB'),
                'pelacakan' => $order->tracking_history ? json_decode($order->tracking_history, true) : [],
            ];
        })->toArray();
    }

    public function render()
    {
        $semuaData = collect($this->getDataDariDatabase());

        // Hitung jumlah badge "Perlu Dikirim"
        $countPerluDikirim = $semuaData->where('status', 'perlu_dikirim')->count();

        // Filter berdasarkan Tab dan Pencarian
        $dataTampil = $semuaData->filter(function ($item) {
            $matchTab = $item['status'] === $this->activeTab;
            $matchSearch = empty($this->search) || 
                           str_contains(strtolower($item['id']), strtolower($this->search)) || 
                           str_contains(strtolower($item['pembeli']), strtolower($this->search)) ||
                           str_contains(strtolower($item['no_resi']), strtolower($this->search));
            
            return $matchTab && $matchSearch;
        });

        return view('livewire.pemasok.pengiriman-logistik', [
            'pengiriman' => $dataTampil,
            'countPerluDikirim' => $countPerluDikirim
        ])->layout('layouts.app');
    }
}