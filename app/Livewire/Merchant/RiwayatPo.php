<?php

namespace App\Livewire\Merchant;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Models\SupplyOrder;
use App\Models\User;
use App\Notifications\PoRevisionResubmitted;

class RiwayatPo extends Component
{
    use WithPagination;

    public string $statusFilter = 'semua'; // semua | menunggu_pemasok | menunggu_lkbb | revisi | diproses_pemasok | dikirim | selesai | ditolak
    public string $search = '';

    // Modal Detail / Review Ulang
    public ?int $selectedOrderId = null;
    public bool $showModal = false;

    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function orders()
    {
        $query = SupplyOrder::with(['pemasok.pemasokProfile', 'details'])
            ->where('merchant_id', Auth::id());

        if ($this->statusFilter !== 'semua') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->where('nomor_order', 'like', '%'.trim($this->search).'%');
        }

        return $query->latest('updated_at')->paginate(10);
    }

    #[Computed]
    public function selectedOrder(): ?SupplyOrder
    {
        if (! $this->selectedOrderId) return null;
        return SupplyOrder::with(['pemasok.pemasokProfile', 'details'])
            ->where('merchant_id', Auth::id())
            ->find($this->selectedOrderId);
    }

    public function openDetail(int $id): void
    {
        $this->selectedOrderId = $id;
        $this->showModal = true;
    }

    public function closeDetail(): void
    {
        $this->showModal = false;
        $this->selectedOrderId = null;
    }

    /**
     * Ajukan Review Ulang: hanya dari status 'revisi' → kembali ke 'menunggu_lkbb'.
     * Tidak insert PO baru, tidak clone — update status existing.
     */
    public function ajukanReviewUlang(): void
    {
        $order = $this->selectedOrder;
        if (! $order) {
            session()->flash('error', 'Pesanan tidak ditemukan.');
            return;
        }
        if ($order->status !== 'revisi') {
            session()->flash('error', 'Hanya PO berstatus "revisi" yang dapat diajukan ulang.');
            return;
        }

        $stamp = 'Diajukan ulang merchant ('.now()->format('d M Y H:i').')';
        $order->update([
            'status'  => 'menunggu_lkbb',
            'catatan' => $order->catatan ? $order->catatan."\n".$stamp : $stamp,
        ]);

        // Notifikasi ke semua LKBB user
        $namaMerchant = $order->merchant->merchantProfile->nama_kantin ?? Auth::user()->name;
        foreach (User::where('role', 'lkbb')->get() as $lkbb) {
            $lkbb->notify(new PoRevisionResubmitted($order->nomor_order, $namaMerchant));
        }

        session()->flash('success', "PO {$order->nomor_order} berhasil diajukan ulang ke LKBB.");
        $this->closeDetail();
    }

    public function render()
    {
        return view('livewire.merchant.riwayat-po')->layout('layouts.app');
    }
}
