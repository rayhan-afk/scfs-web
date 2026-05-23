<?php

namespace App\Livewire\Lkbb;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\SupplyOrder;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\Lkbb\PoValidationService;
use App\Notifications\PoNeedsRevision;
use Illuminate\Support\Facades\DB;

class ApprovalPo extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedOrder = null;
    public $showModal = false;
    public $alasanPenolakan = '';

    /**
     * 5 kriteria otomatis (di-set dari PoValidationService).
     * 1 kriteria manual: 'no_tunggakan' (di-toggle approver LKBB).
     */
    public array $validationChecklist = [
        'merchant_verified' => false,
        'supplier_verified' => false,
        'rekening_valid'    => false,
        'no_tunggakan'      => false,
        'po_complete'       => false,
    ];

    /** Reason string per-kriteria saat hasil evaluasi otomatis = false. */
    public array $validationReasons = [];

    /** Daftar key kriteria yang di-evaluasi otomatis (read-only di UI). */
    public const AUTO_KEYS = [
        'merchant_verified',
        'supplier_verified',
        'rekening_valid',
        'po_complete',
    ];

    #[Computed]
    public function isChecklistComplete(): bool
    {
        return ! in_array(false, $this->validationChecklist, true);
    }

    private function resetChecklist(): void
    {
        foreach ($this->validationChecklist as $k => $_) {
            $this->validationChecklist[$k] = false;
        }
        $this->validationReasons = [];
    }

    private function autoEvaluateChecklist(): void
    {
        if (! $this->selectedOrder) return;

        $result = app(PoValidationService::class)->evaluate($this->selectedOrder);

        foreach (self::AUTO_KEYS as $key) {
            $this->validationChecklist[$key]  = $result[$key]['passed'] ?? false;
            $this->validationReasons[$key]    = $result[$key]['reason'] ?? null;
        }
    }

    public function render()
    {
        $orders = SupplyOrder::with(['merchant.merchantProfile', 'pemasok.pemasokProfile', 'details'])
            ->where('status', 'menunggu_lkbb')
            ->when($this->search, function ($query) {
                $query->where('nomor_order', 'like', '%' . $this->search . '%')
                      ->orWhereHas('merchant', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('merchant.merchantProfile', function($q) {
                          $q->where('nama_kantin', 'like', '%' . $this->search . '%');
                      });
            })
            ->latest()
            ->paginate(10);

        $brankasInvestasi = Wallet::where('type', 'LKBB_INVESTMENT')->first();

        return view('livewire.lkbb.approval-po', [
            'orders' => $orders,
            'saldoInvestasi' => $brankasInvestasi ? $brankasInvestasi->balance : 0
        ])->layout('layouts.lkbb'); 
    }

    public function bukaModal($id)
    {
        $this->selectedOrder = SupplyOrder::with([
            'merchant.merchantProfile',
            'pemasok.pemasokProfile',
            'details',
        ])->findOrFail($id);

        $this->alasanPenolakan = '';
        $this->resetChecklist();
        $this->autoEvaluateChecklist();
        $this->showModal = true;
    }

    public function tutupModal()
    {
        $this->showModal = false;
        $this->selectedOrder = null;
        $this->resetChecklist();
    }

    public function setujuiPendanaan()
    {
        if (!$this->selectedOrder) return;

        // Re-evaluasi kriteria otomatis untuk cegah tampering client-side
        // pada checklist read-only sebelum approval di-eksekusi.
        $this->autoEvaluateChecklist();

        if (in_array(false, $this->validationChecklist, true)) {
            session()->flash('error', 'Validasi pendanaan belum lengkap. Pastikan seluruh kriteria terpenuhi sebelum mencairkan dana.');
            return;
        }

        try {
            DB::transaction(function () {
                $order = SupplyOrder::lockForUpdate()->findOrFail($this->selectedOrder->id);
                
                // DOUBLE PROTECTION: Cegah double click atau data basi
                if ($order->status !== 'menunggu_lkbb') {
                    throw new \Exception("Pesanan ini sudah diproses sebelumnya.");
                }

                // 1. Validasi Brankas
                $brankasLKBB = Wallet::where('type', 'LKBB_INVESTMENT')->lockForUpdate()->first();
                if (!$brankasLKBB || $brankasLKBB->balance < $order->total_estimasi) {
                    throw new \Exception("Saldo Brankas Investasi LKBB tidak mencukupi untuk mendanai PO ini.");
                }

                // 2. Potong Saldo LKBB
                $brankasLKBB->decrement('balance', $order->total_estimasi);

                // 3. Catat Transaksi (Sebagai tanda bukti transfer sistem ke Pemasok)
                Transaction::create([
                    'order_id' => $order->nomor_order,
                    'user_id' => $order->pemasok_id, 
                    'merchant_id' => $order->merchant_id, // Kita rekam juga merchantnya biar transparan
                    'sender_wallet_id' => $brankasLKBB->id,
                    'type' => 'PEMBIAYAAN_PO',
                    'status' => 'success',
                    'total_amount' => $order->total_estimasi,
                    'description' => "Pencairan dana PO ke Pemasok untuk Kantin: " . ($order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name)
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

            session()->flash('success', "Dana Rp " . number_format($this->selectedOrder->total_estimasi, 0, ',', '.') . " berhasil dicairkan ke Pemasok. Stok otomatis di-booking!");
            $this->tutupModal();

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Minta Revisi: PO tetap hidup, merchant bisa perbaiki masalah lalu
     * mengajukan ulang ke status menunggu_lkbb. Tidak memotong saldo brankas.
     */
    public function mintaRevisi()
    {
        $this->validate([
            'alasanPenolakan' => 'required|min:5',
        ]);

        if (!$this->selectedOrder || $this->selectedOrder->status !== 'menunggu_lkbb') {
            session()->flash('error', 'Aksi tidak valid: status PO sudah berubah.');
            return;
        }

        $catatan = 'Revisi LKBB ('.now()->format('d M Y H:i').'): '.$this->alasanPenolakan;

        $this->selectedOrder->update([
            'status'  => 'revisi',
            'catatan' => $this->selectedOrder->catatan
                ? $this->selectedOrder->catatan."\n".$catatan
                : $catatan,
        ]);

        $merchant = $this->selectedOrder->merchant;
        if ($merchant) {
            $merchant->notify(new PoNeedsRevision(
                $this->selectedOrder->nomor_order,
                $this->alasanPenolakan
            ));
        }

        session()->flash('success', "PO {$this->selectedOrder->nomor_order} diminta revisi. Merchant akan diberitahu.");
        $this->tutupModal();
    }

    /**
     * Tolak Final: PO ditutup permanen, tidak bisa diajukan ulang.
     * Pakai ini hanya untuk kasus pelanggaran berat / fraud / data tidak valid total.
     */
    public function tolakFinal()
    {
        $this->validate([
            'alasanPenolakan' => 'required|min:5',
        ]);

        if (!$this->selectedOrder || $this->selectedOrder->status !== 'menunggu_lkbb') {
            session()->flash('error', 'Aksi tidak valid: status PO sudah berubah.');
            return;
        }

        $this->selectedOrder->update([
            'status'  => 'ditolak',
            'catatan' => 'Ditolak final LKBB: ' . $this->alasanPenolakan,
        ]);

        session()->flash('error', "Pengajuan PO {$this->selectedOrder->nomor_order} telah ditolak final dan dibatalkan.");
        $this->tutupModal();
    }
}