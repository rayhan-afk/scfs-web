<?php

namespace App\Livewire\Merchant;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SupplyOrder;
use App\Models\PengajuanReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FormReturn extends Component
{
    use WithFileUploads;

    public $orderId;

    // Form input
    public string $tipe_masalah = '';
    public int $qty_bermasalah = 1;
    public string $deskripsi_masalah = '';
    public array $foto_bukti_uploads = []; // multi
    public $video_bukti_upload;             // single
    public string $solusi_diajukan = 'refund';
    public ?int $supply_order_detail_id = null; // optional item-level

    public function mount($orderId)
    {
        $this->orderId = (int) $orderId;

        $order = SupplyOrder::with('details')
            ->where('id', $this->orderId)
            ->where('merchant_id', Auth::id())
            ->firstOrFail();

        // Default qty: total qty pertama (helper UX)
        $first = $order->details->first();
        if ($first) {
            $this->qty_bermasalah = (int) $first->qty;
        }
    }

    public function simpanReturn()
    {
        $order = SupplyOrder::with('details')
            ->where('id', $this->orderId)
            ->where('merchant_id', Auth::id())
            ->firstOrFail();

        // ─────────── BUSINESS RULE GUARDS ───────────

        // 1. Order harus sudah dikirim/diterima
        if (!in_array($order->status, ['dikirim', 'selesai'], true)) {
            $this->addError('orderStatus', 'Return hanya bisa diajukan setelah barang dikirim pemasok.');
            return;
        }

        // 2. Anti-duplicate: tidak boleh ada return lain yang masih open untuk order ini
        $hasOpen = PengajuanReturn::where('supply_order_id', $order->id)
            ->where('merchant_id', Auth::id())
            ->whereIn('status', ['pending_supplier_review', 'approved', 'escalated_lkbb'])
            ->exists();
        if ($hasOpen) {
            $this->addError('orderStatus', 'Sudah ada pengajuan return aktif untuk pesanan ini. Tunggu keputusan dulu.');
            return;
        }

        // 3. Deadline window: 24 jam sejak status terakhir berubah ke 'dikirim'/'selesai'
        $cutoff = $order->updated_at?->addHours(24);
        if ($cutoff && now()->greaterThan($cutoff)) {
            $this->addError('orderStatus', 'Batas waktu pengajuan return (24 jam sejak diterima) sudah lewat.');
            return;
        }

        // ─────────── VALIDATION ───────────
        $this->validate([
            'tipe_masalah'          => 'required|in:'.implode(',', array_keys(PengajuanReturn::TYPES)),
            'qty_bermasalah'        => 'required|integer|min:1',
            'deskripsi_masalah'     => 'required|string|min:10|max:1000',
            'foto_bukti_uploads'    => 'required|array|min:1|max:5',
            'foto_bukti_uploads.*'  => 'image|mimes:jpeg,png,jpg|max:2048',
            'video_bukti_upload'    => 'nullable|file|mimes:mp4,mov,webm|max:20480', // 20MB
            'solusi_diajukan'       => 'required|in:'.implode(',', array_keys(PengajuanReturn::SOLUTIONS)),
            'supply_order_detail_id'=> 'nullable|integer|exists:supply_order_details,id',
        ]);

        // ─────────── UPLOAD FILES ───────────
        $fotoPaths = [];
        foreach ($this->foto_bukti_uploads as $foto) {
            $fotoPaths[] = $foto->store('returns/foto', 'public');
        }
        $videoPath = $this->video_bukti_upload
            ? $this->video_bukti_upload->store('returns/video', 'public')
            : null;

        // ─────────── CREATE ───────────
        $return = PengajuanReturn::create([
            'supply_order_id'        => $order->id,
            'supply_order_detail_id' => $this->supply_order_detail_id,
            'merchant_id'            => Auth::id(),
            'supplier_id'            => $order->pemasok_id,
            'tipe_masalah'           => $this->tipe_masalah,
            'qty_bermasalah'         => $this->qty_bermasalah,
            'deskripsi_masalah'      => $this->deskripsi_masalah,
            'foto_bukti'             => $fotoPaths,
            'video_bukti'            => $videoPath,
            'solusi_diajukan'        => $this->solusi_diajukan,
            'status'                 => 'pending_supplier_review',
            'deadline_at'            => now()->addHours(PengajuanReturn::DEADLINE_HOURS),
        ]);

        $return->appendAudit('merchant', 'created', "Tipe: {$this->tipe_masalah} | Solusi: {$this->solusi_diajukan}");

        // Fraud heuristic: jika merchant ini sudah 3+ return dalam 7 hari → flag
        $recentCount = PengajuanReturn::where('merchant_id', Auth::id())
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        if ($recentCount >= 3) {
            $return->update(['flag_fraud' => true]);
            $return->appendAudit('system', 'fraud_flag', "Merchant {$recentCount} returns dalam 7 hari terakhir.");
        }

        session()->flash('message', 'Pengajuan return berhasil dikirim. Pemasok akan merespons dalam '.PengajuanReturn::DEADLINE_HOURS.' jam.');
        return redirect()->route('merchant.form-return', $order->id);
    }

    public function ajukanBanding($returnId)
    {
        $return = PengajuanReturn::where('id', $returnId)
            ->where('merchant_id', Auth::id())
            ->firstOrFail();

        if (!$return->canBeAppealed()) {
            session()->flash('error', 'Return ini tidak dapat dibanding.');
            return;
        }

        $return->update(['status' => 'escalated_lkbb']);
        $return->appendAudit('merchant', 'appealed', 'Merchant mengajukan banding ke LKBB.');

        session()->flash('message', 'Banding berhasil dikirim. LKBB akan meninjau sengketa.');
    }

    public function render()
    {
        $order = SupplyOrder::with('details')->findOrFail($this->orderId);

        $riwayatReturns = PengajuanReturn::where('merchant_id', Auth::id())
            ->where('supply_order_id', $this->orderId)
            ->latest()
            ->get();

        return view('livewire.merchant.form-return', [
            'order'           => $order,
            'riwayat_returns' => $riwayatReturns,
            'types'           => PengajuanReturn::TYPES,
            'solutions'       => PengajuanReturn::SOLUTIONS,
        ])->layout('layouts.app');
    }
}
