<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;

class PengajuanDanaLkbb extends Component
{
    public $activeTab = 'siap_diajukan';
    
    // Info Plafon LKBB
    public $plafonTotal = 500000000;
    public $plafonTerpakai = 150000000;
    
    // State untuk Pengajuan
    public array $selectedPesanan = [];
    public $showModalKonfirmasi = false;
    public $pemasokProfile;

    public array $dataPesanan = [];

    public function mount()
    {
       // Pastikan user sedang login
        if (auth()->check()) {
            $this->pemasokProfile = \App\Models\PemasokProfile::where('user_id', auth()->id())->first();
        }
        // Data dummy PO sesuai SOP (Mulai dari PO baru, sampai yang sudah produksi)
        $this->dataPesanan = [
            [
                'id' => 'PO-2026-001',
                'merchant' => 'Toko Kelontong Berkah',
                'nominal' => 15000000,
                'tanggal' => now()->format('d M Y'),
                'status' => 'siap_diajukan',
                'id_pengajuan' => null
            ],
            [
                'id' => 'PO-2026-002',
                'merchant' => 'Warung Makmur',
                'nominal' => 8500000,
                'tanggal' => now()->subDay()->format('d M Y'),
                'status' => 'siap_diajukan',
                'id_pengajuan' => null
            ],
            [
                'id' => 'PO-2026-003',
                'merchant' => 'Grosir Sembako Maju',
                'nominal' => 45000000,
                'tanggal' => now()->subDays(2)->format('d M Y'),
                'status' => 'menunggu_lkbb', // Sedang diproses bank (Aktivitas 5)
                'id_pengajuan' => 'REQ-LKBB-991'
            ],
            [
                'id' => 'PO-2026-004',
                'merchant' => 'Minimarket Sentosa',
                'nominal' => 25000000,
                'tanggal' => now()->subDays(3)->format('d M Y'),
                'status' => 'dicairkan', // Sudah cair, siap produksi (Aktivitas 6)
                'id_pengajuan' => 'REQ-LKBB-980'
            ],
            [
                'id' => 'PO-2026-005',
                'merchant' => 'Kantin Kampus Utama',
                'nominal' => 12000000,
                'tanggal' => now()->subDays(4)->format('d M Y'),
                'status' => 'sedang_diproduksi', // Sedang diolah/QC (Aktivitas 7)
                'id_pengajuan' => 'REQ-LKBB-975'
            ],
            
        ];
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedPesanan = []; // Reset pilihan jika ganti tab
    }

    public function getTotalPengajuanProperty()
    {
        return collect($this->dataPesanan)
            ->whereIn('id', $this->selectedPesanan)
            ->sum('nominal');
    }

    public function bukaModalKonfirmasi()
    {
        if(count($this->selectedPesanan) > 0) {
            $this->showModalKonfirmasi = true;
        } else {
            session()->flash('error', 'Pilih minimal satu pesanan untuk diajukan.');
        }
    }

    // FASE B - Aktivitas 5: Mengajukan dana ke LKBB
    public function kirimPengajuan()
    {
        $totalDiajukan = $this->totalPengajuan;
        $sisaLimit = $this->plafonTotal - $this->plafonTerpakai;

        if ($totalDiajukan > $sisaLimit) {
            session()->flash('error_modal', 'Total pengajuan melebihi sisa limit plafon LKBB!');
            return;
        }

        // Simulasikan penambahan plafon terpakai
        $this->plafonTerpakai += $totalDiajukan;

        // Proses update status pesanan menjadi 'menunggu_lkbb'
        $idPengajuanBaru = 'REQ-LKBB-' . rand(1000, 9999);
        
        foreach ($this->dataPesanan as $key => $pesanan) {
            if (in_array($pesanan['id'], $this->selectedPesanan)) {
                $this->dataPesanan[$key]['status'] = 'menunggu_lkbb';
                $this->dataPesanan[$key]['id_pengajuan'] = $idPengajuanBaru;
            }
        }

        $this->showModalKonfirmasi = false;
        $this->selectedPesanan = [];
        $this->setTab('menunggu_lkbb');
        session()->flash('message', 'Pengajuan dana berhasil dikirim! Menunggu persetujuan pencairan.');
    }

    // FASE B - Aktivitas 7 (Awal): Mulai Produksi setelah Dana Cair
    public function mulaiProduksi($idPesanan)
    {
        foreach ($this->dataPesanan as $key => $pesanan) {
            if ($pesanan['id'] === $idPesanan) {
                $this->dataPesanan[$key]['status'] = 'sedang_diproduksi';
                session()->flash('message', "Pesanan {$idPesanan} mulai diproduksi!");
                break;
            }
        }
        $this->setTab('sedang_diproduksi');
    }

    // FASE B - Aktivitas 7 (Akhir): Selesai QC dan Siap Kirim
    public function selesaiQC($idPesanan)
    {
        foreach ($this->dataPesanan as $key => $pesanan) {
            if ($pesanan['id'] === $idPesanan) {
                $this->dataPesanan[$key]['status'] = 'siap_dikirim';
                session()->flash('message', "Pesanan {$idPesanan} lolos QC dan siap dikirim ke Merchant!");
                break;
            }
        }
        $this->setTab('siap_dikirim');
    }

    public function render()
    {
        $pesananDitampilkan = collect($this->dataPesanan)->where('status', $this->activeTab);
        $sisaPlafon = $this->plafonTotal - $this->plafonTerpakai;
        $persentaseTerpakai = ($this->plafonTerpakai / $this->plafonTotal) * 100;

        return view('livewire.pemasok.pengajuan-dana-lkbb', [
            'pesananDitampilkan' => $pesananDitampilkan,
            'sisaPlafon' => $sisaPlafon,
            'persentaseTerpakai' => $persentaseTerpakai
        ])->layout('layouts.app');
    }
}