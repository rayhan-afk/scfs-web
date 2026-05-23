<?php

namespace App\Livewire\Pemasok;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\PengajuanReturn;
use Illuminate\Support\Facades\Auth;

class ManajemenReturn extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending_supplier_review';
    public string $search = '';

    // Modal & action state
    public ?int $selectedId = null;
    public bool $showModal = false;
    public string $catatan = '';
    public string $keputusan_resolusi = ''; // hanya saat approve

    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function returns()
    {
        $q = PengajuanReturn::with(['merchant', 'supplyOrder', 'supplyOrderDetail'])
            ->where('supplier_id', Auth::id());

        if ($this->statusFilter !== 'semua') {
            $q->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $q->whereHas('supplyOrder', fn($s) => $s->where('nomor_order', 'like', '%'.trim($this->search).'%'));
        }

        return $q->latest()->paginate(10);
    }

    #[Computed]
    public function selected(): ?PengajuanReturn
    {
        if (!$this->selectedId) return null;
        return PengajuanReturn::with(['merchant', 'supplyOrder', 'supplyOrderDetail'])
            ->where('supplier_id', Auth::id())
            ->find($this->selectedId);
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'pending'  => PengajuanReturn::where('supplier_id', Auth::id())->where('status', 'pending_supplier_review')->count(),
            'approved' => PengajuanReturn::where('supplier_id', Auth::id())->where('status', 'approved')->count(),
            'rejected' => PengajuanReturn::where('supplier_id', Auth::id())->where('status', 'rejected')->count(),
            'escalated'=> PengajuanReturn::where('supplier_id', Auth::id())->where('status', 'escalated_lkbb')->count(),
        ];
    }

    public function openDetail(int $id): void
    {
        $this->selectedId = $id;
        $this->catatan = '';
        $this->keputusan_resolusi = '';
        $this->showModal = true;
    }

    public function closeDetail(): void
    {
        $this->showModal = false;
        $this->selectedId = null;
        $this->resetValidation();
    }

    public function approve(): void
    {
        $this->validate([
            'catatan'            => 'required|string|min:5|max:500',
            'keputusan_resolusi' => 'required|in:'.implode(',', array_keys(PengajuanReturn::SOLUTIONS)),
        ], [], [
            'catatan'            => 'catatan persetujuan',
            'keputusan_resolusi' => 'jenis resolusi',
        ]);

        $ret = $this->selected;
        if (!$ret || $ret->status !== 'pending_supplier_review') {
            session()->flash('error', 'Aksi tidak valid: status sudah berubah.');
            $this->closeDetail();
            return;
        }

        $ret->update([
            'status'             => 'approved',
            'keputusan_resolusi' => $this->keputusan_resolusi,
            'catatan_pemasok'    => $this->catatan,
        ]);
        $ret->appendAudit('pemasok', 'approved', "Resolusi: {$this->keputusan_resolusi}");

        session()->flash('message', 'Return disetujui dengan resolusi: '.PengajuanReturn::SOLUTIONS[$this->keputusan_resolusi]);
        $this->closeDetail();
    }

    public function reject(): void
    {
        $this->validate([
            'catatan' => 'required|string|min:5|max:500',
        ], [], ['catatan' => 'alasan penolakan']);

        $ret = $this->selected;
        if (!$ret || $ret->status !== 'pending_supplier_review') {
            session()->flash('error', 'Aksi tidak valid: status sudah berubah.');
            $this->closeDetail();
            return;
        }

        $ret->update([
            'status'          => 'rejected',
            'catatan_pemasok' => $this->catatan,
        ]);
        $ret->appendAudit('pemasok', 'rejected', $this->catatan);

        session()->flash('message', 'Return ditolak. Merchant dapat mengajukan banding ke LKBB jika tidak setuju.');
        $this->closeDetail();
    }

    public function render()
    {
        return view('livewire.pemasok.manajemen-return')->layout('layouts.app');
    }
}
