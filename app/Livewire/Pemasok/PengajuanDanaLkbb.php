<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use App\Models\SupplyOrder;
use App\Models\PemasokProfile;
use Livewire\Attributes\Computed;

class PengajuanDanaLkbb extends Component
{
    public $activeTab = 'siap_diajukan';
    
    // Info Plafon LKBB (Sementara hardcode, nanti bisa dinamis)
    public $plafonTotal = 500000000;
    public $plafonTerpakai = 150000000;
    
    // State
    public array $selectedPesanan = [];
    public $showModalKonfirmasi = false;
    public $pemasokProfile;

    public function mount()
    {
        if (auth()->check()) {
            $this->pemasokProfile = PemasokProfile::where('user_id', auth()->id())->first();
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedPesanan = []; 
    }

    // Mengambil data dari database berdasarkan pemasok_id dan status_pembiayaan
    #[Computed]
    public function pesananDitampilkan()
    {
        return SupplyOrder::with(['merchant'])
            ->where('pemasok_id', auth()->id())
            ->where('status_pembiayaan', $this->activeTab)
            ->latest()
            ->get();
    }

    // Hitung total nominal dari checkbox yang dipilih
    #[Computed]
    public function totalPengajuan()
    {
        return SupplyOrder::whereIn('id', $this->selectedPesanan)->sum('total_estimasi');
    }

    public function bukaModalKonfirmasi()
    {
        if(count($this->selectedPesanan) > 0) {
            $this->showModalKonfirmasi = true;
        } else {
            session()->flash('error', 'Pilih minimal satu pesanan untuk diajukan.');
        }
    }

    public function kirimPengajuan()
    {
        $totalDiajukan = $this->totalPengajuan;
        $sisaLimit = $this->plafonTotal - $this->plafonTerpakai;

        if ($totalDiajukan > $sisaLimit) {
            session()->flash('error', 'Total pengajuan melebihi sisa limit plafon LKBB!');
            $this->showModalKonfirmasi = false;
            return;
        }

        $this->plafonTerpakai += $totalDiajukan;
        
        // Ambil salah satu order untuk mendapatkan merchant_id
        $sampleOrder = SupplyOrder::find($this->selectedPesanan[0]);

        // 1. BUAT DATA DI LKBB DULU
        // Kita tidak perlu isi 'invoice_number' karena model SupplyChain sudah otomatis membuatnya (INV-SC-...)
        $margin = $totalDiajukan * 0.05; // Contoh margin 5%
        $supplyChain = \App\Models\SupplyChain::create([
            'merchant_id' => $sampleOrder->merchant_id,
            'supplier_id' => auth()->id(),
            'item_description' => 'Pengajuan Dana untuk ' . count($this->selectedPesanan) . ' pesanan.',
            'capital_amount' => $totalDiajukan,
            'margin_amount' => $margin,
            'total_amount' => $totalDiajukan + $margin,
            'status' => 'PENDING',
            'due_date' => now()->addDays(30),
        ]);
        
        // 2. SIMPAN RESINYA KE PESANAN PEMASOK
        // Gunakan invoice_number dari $supplyChain sebagai 'id_pengajuan'
        SupplyOrder::whereIn('id', $this->selectedPesanan)->update([
            'status_pembiayaan' => 'menunggu_lkbb',
            'id_pengajuan' => $supplyChain->invoice_number // <--- INI JEMBATANNYA
        ]);

        $this->showModalKonfirmasi = false;
        $this->selectedPesanan = [];
        $this->setTab('menunggu_lkbb');
        session()->flash('message', 'Pengajuan dana berhasil dikirim! Menunggu persetujuan pencairan.');
    }
    public function mulaiProduksi($idPesanan)
    {
        // JEMBATAN KE FITUR PESANAN MASUK: Update 2 status sekaligus
        SupplyOrder::where('id', $idPesanan)->update([
            'status_pembiayaan' => 'sedang_diproduksi',
            'status' => 'diproses_pemasok' // Ini agar muncul di tab "Diproses" di fitur Pesanan Masuk
        ]);

        $this->setTab('sedang_diproduksi');
        session()->flash('message', "Pesanan mulai diproduksi!");
    }

    public function selesaiQC($idPesanan)
    {
        SupplyOrder::where('id', $idPesanan)->update([
            'status_pembiayaan' => 'siap_dikirim'
        ]);

        $this->setTab('siap_dikirim');
        session()->flash('message', "Pesanan lolos QC dan siap dikirim!");
    }

    public function render()
    {
        $sisaPlafon = $this->plafonTotal - $this->plafonTerpakai;
        $persentaseTerpakai = ($this->plafonTerpakai / $this->plafonTotal) * 100;

        return view('livewire.pemasok.pengajuan-dana-lkbb', [
            'sisaPlafon' => $sisaPlafon,
            'persentaseTerpakai' => $persentaseTerpakai
        ])->layout('layouts.app');
    }
}