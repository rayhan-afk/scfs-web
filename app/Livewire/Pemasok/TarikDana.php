<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;

class TarikDana extends Component
{
    public $activeTab = 'siap_ditarik';
    
    // Info Saldo Pemasok
    public $saldoTersedia = 45000000; // Total saldo yang bisa ditarik
    public $saldoDitahan = 5000000;   // Saldo yang belum cair sepenuhnya
    
    // State Penarikan
    public array $selectedPendapatan = [];
    public $showModalKonfirmasi = false;
    public $pemasokProfile;

    public array $dataPendapatan = [];

    public function mount()
    {
        if (auth()->check()) {
            $this->pemasokProfile = \App\Models\PemasokProfile::where('user_id', auth()->id())->first();
        }

        // Data dummy Pendapatan dari pesanan yang sudah selesai
        $this->dataPendapatan = [
            [
                'id' => 'WD-2026-001',
                'sumber' => 'Pelunasan PO-2026-001 (Toko Kelontong Berkah)',
                'nominal' => 15000000,
                'tanggal' => now()->format('d M Y'),
                'status' => 'siap_ditarik',
                'id_penarikan' => null
            ],
            [
                'id' => 'WD-2026-002',
                'sumber' => 'Pelunasan PO-2026-002 (Warung Makmur)',
                'nominal' => 8500000,
                'tanggal' => now()->subDay()->format('d M Y'),
                'status' => 'siap_ditarik',
                'id_penarikan' => null
            ],
            [
                'id' => 'WD-2026-003',
                'sumber' => 'Pelunasan PO-2026-008 (Grosir Sembako Maju)',
                'nominal' => 20000000,
                'tanggal' => now()->subDays(2)->format('d M Y'),
                'status' => 'diproses', // Sedang diproses transfer oleh admin/sistem
                'id_penarikan' => 'TRX-BNK-771'
            ],
            [
                'id' => 'WD-2026-004',
                'sumber' => 'Pelunasan PO-2026-010 (Minimarket Sentosa)',
                'nominal' => 12500000,
                'tanggal' => now()->subDays(5)->format('d M Y'),
                'status' => 'berhasil', // Sudah masuk ke rekening pemasok
                'id_penarikan' => 'TRX-BNK-650'
            ],
        ];
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedPendapatan = []; 
    }

    public function getTotalPenarikanProperty()
    {
        return collect($this->dataPendapatan)
            ->whereIn('id', $this->selectedPendapatan)
            ->sum('nominal');
    }

    public function prosesTarikDana()
    {
        $totalDitarik = $this->totalPenarikan;

        if ($totalDitarik > $this->saldoTersedia) {
            session()->flash('error', 'Nominal penarikan melebihi saldo tersedia!');
            $this->showModalKonfirmasi = false;
            return;
        }

        // Kurangi saldo yang tersedia secara simulasi
        $this->saldoTersedia -= $totalDitarik;

        // Update status menjadi 'diproses'
        $idPenarikanBaru = 'TRX-BNK-' . rand(1000, 9999);
        
        foreach ($this->dataPendapatan as $key => $item) {
            if (in_array($item['id'], $this->selectedPendapatan)) {
                $this->dataPendapatan[$key]['status'] = 'diproses';
                $this->dataPendapatan[$key]['id_penarikan'] = $idPenarikanBaru;
            }
        }

        $this->showModalKonfirmasi = false;
        $this->selectedPendapatan = [];
        $this->setTab('diproses');
        
        session()->flash('message', 'Permintaan penarikan dana berhasil dibuat! Menunggu transfer ke rekening Anda.');
    }

    // Simulasi Admin/Sistem menyelesaikan transfer (Opsional, untuk demo)
    public function konfirmasiTransferSelesai($idPenarikan)
    {
        foreach ($this->dataPendapatan as $key => $item) {
            if ($item['id_penarikan'] === $idPenarikan) {
                $this->dataPendapatan[$key]['status'] = 'berhasil';
            }
        }
        session()->flash('message', "Dana dengan ID {$idPenarikan} telah berhasil masuk ke rekening!");
        $this->setTab('berhasil');
    }

    public function render()
    {
        $dataDitampilkan = collect($this->dataPendapatan)->where('status', $this->activeTab);

        return view('livewire.pemasok.tarik-dana', [
            'dataDitampilkan' => $dataDitampilkan
        ])->layout('layouts.app');
    }
}