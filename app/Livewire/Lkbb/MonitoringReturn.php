<?php

namespace App\Livewire\Lkbb;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\PengajuanReturn;

class MonitoringReturn extends Component
{
    use WithPagination;

    public string $statusFilter = 'escalated_lkbb'; // default: yang butuh aksi
    public string $search = '';
    public bool $onlyFraud = false;

    // Modal & action
    public ?int $selectedId = null;
    public bool $showModal = false;
    public string $catatan = '';
    public string $keputusan = ''; // menangkan_merchant_refund | menangkan_merchant_replace | menangkan_pemasok

    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedOnlyFraud(): void { $this->resetPage(); }

    #[Computed]
    public function returns()
    {
        $q = PengajuanReturn::with(['merchant', 'supplier', 'supplyOrder']);

        if ($this->statusFilter !== 'semua') {
            $q->where('status', $this->statusFilter);
        }
        if ($this->onlyFraud) {
            $q->where('flag_fraud', true);
        }
        if ($this->search) {
            $q->whereHas('supplyOrder', fn($s) => $s->where('nomor_order', 'like', '%'.trim($this->search).'%'));
        }

        return $q->latest()->paginate(15);
    }

    #[Computed]
    public function selected(): ?PengajuanReturn
    {
        if (!$this->selectedId) return null;
        return PengajuanReturn::with(['merchant', 'supplier', 'supplyOrder'])->find($this->selectedId);
    }

    #[Computed]
    public function analytics(): array
    {
        return [
            'total'      => PengajuanReturn::count(),
            'pending'    => PengajuanReturn::where('status', 'pending_supplier_review')->count(),
            'escalated'  => PengajuanReturn::where('status', 'escalated_lkbb')->count(),
            'resolved'   => PengajuanReturn::where('status', 'resolved')->count(),
            'fraud_flag' => PengajuanReturn::where('flag_fraud', true)->count(),
        ];
    }

    public function openDetail(int $id): void
    {
        $this->selectedId = $id;
        $this->catatan = '';
        $this->keputusan = '';
        $this->showModal = true;
    }

    public function closeDetail(): void
    {
        $this->showModal = false;
        $this->selectedId = null;
        $this->resetValidation();
    }

    public function putuskan(): void
    {
        $this->validate([
            'keputusan' => 'required|in:menangkan_merchant_refund,menangkan_merchant_replace,menangkan_pemasok',
            'catatan'   => 'required|string|min:10|max:1000',
        ], [], [
            'keputusan' => 'keputusan arbitrase',
            'catatan'   => 'alasan keputusan',
        ]);

        $ret = $this->selected;
        if (!$ret || $ret->status !== 'escalated_lkbb') {
            session()->flash('error', 'Aksi tidak valid: hanya banding yang dapat diputuskan.');
            $this->closeDetail();
            return;
        }

        $ret->update([
            'status'             => 'resolved',
            'keputusan_resolusi' => $this->keputusan,
            'catatan_lkbb'       => $this->catatan,
        ]);
        $ret->appendAudit('lkbb', 'resolved', "Keputusan: {$this->keputusan}");

        session()->flash('message', 'Sengketa resmi ditutup dengan keputusan final.');
        $this->closeDetail();
    }

    public function toggleFraud(int $id): void
    {
        $ret = PengajuanReturn::find($id);
        if (!$ret) return;
        $ret->update(['flag_fraud' => !$ret->flag_fraud]);
        $ret->appendAudit('lkbb', $ret->flag_fraud ? 'fraud_flagged' : 'fraud_cleared', null);
    }

    public function render()
    {
        return view('livewire.lkbb.monitoring-return')->layout('layouts.lkbb');
    }
}
