<?php

namespace App\Livewire\Lkbb;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SupplyOrder;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ApprovalPo extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedOrder = null;
    public $showModal = false;
    public $alasanPenolakan = '';

    public function render()
    {
        // Mengambil order yang statusnya menunggu_lkbb, lengkapi dengan relasi merchant & pemasok
        $orders = SupplyOrder::with(['merchant.merchantProfile', 'pemasok.pemasokProfile', 'details'])
            ->where('status', 'menunggu_lkbb')
            ->when($this->search, function ($query) {
                $query->where('nomor_order', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        // Ambil saldo brankas investasi LKBB untuk ditampilkan di dashboard
        $brankasInvestasi = Wallet::where('type', 'LKBB_INVESTMENT')->first();

        return view('livewire.lkbb.approval-po', [
            'orders' => $orders,
            'saldoInvestasi' => $brankasInvestasi ? $brankasInvestasi->balance : 0
        ])->layout('layouts.lkbb'); 
    }

    public function bukaModal($id)
    {
        $this->selectedOrder = SupplyOrder::with(['merchant.merchantProfile', 'pemasok.pemasokProfile', 'details'])->findOrFail($id);
        $this->alasanPenolakan = '';
        $this->showModal = true;
    }

    public function tutupModal()
    {
        $this->showModal = false;
        $this->selectedOrder = null;
    }

    public function setujuiPendanaan()
    {
        if (!$this->selectedOrder) return;

        try {
            DB::transaction(function () {
                $order = SupplyOrder::lockForUpdate()->findOrFail($this->selectedOrder->id);
                
                // 1. Validasi Brankas
                $brankasLKBB = Wallet::where('type', 'LKBB_INVESTMENT')->lockForUpdate()->first();
                if (!$brankasLKBB || $brankasLKBB->balance < $order->total_estimasi) {
                    throw new \Exception("Saldo Brankas Investasi LKBB tidak mencukupi untuk mendanai PO ini.");
                }

                // 2. Potong Saldo LKBB
                $brankasLKBB->decrement('balance', $order->total_estimasi);

                // 3. Catat Transaksi
                Transaction::create([
                    'order_id' => $order->nomor_order,
                    'user_id' => $order->pemasok_id, 
                    'sender_wallet_id' => $brankasLKBB->id,
                    'type' => 'PEMBIAYAAN_PO',
                    'status' => 'success',
                    'total_amount' => $order->total_estimasi,
                    'description' => "Pencairan dana PO ke Pemasok untuk Kantin ID: " . $order->merchant_id
                ]);

                // 4. POTONG STOK BARANG PEMASOK SECARA OTOMATIS
                foreach ($order->details as $detail) {
                    if ($detail->produkPemasok) {
                        // Stok dikurangi sesuai jumlah pesanan merchant
                        $detail->produkPemasok->decrement('stok_sekarang', $detail->qty);
                    }
                }

                // 5. Ubah status PO
                $order->update([
                    'status' => 'diproses_pemasok',
                    'status_pembiayaan' => 'didanai'
                ]);
            });

            session()->flash('success', "Dana berhasil dicairkan dan Stok Pemasok otomatis telah di-booking!");
            $this->tutupModal();

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function tolakPendanaan()
    {
        $this->validate([
            'alasanPenolakan' => 'required|min:5'
        ]);

        if ($this->selectedOrder) {
            $this->selectedOrder->update([
                'status' => 'ditolak',
                'catatan' => 'Ditolak LKBB: ' . $this->alasanPenolakan
            ]);

            session()->flash('error', "Pengajuan PO telah ditolak.");
            $this->tutupModal();
        }
    }
}