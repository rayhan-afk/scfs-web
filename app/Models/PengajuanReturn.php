<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanReturn extends Model
{
    protected $fillable = [
        'supply_order_id',
        'supply_order_detail_id',
        'merchant_id',
        'supplier_id',
        'tipe_masalah',
        'qty_bermasalah',
        'deskripsi_masalah',
        'foto_bukti',
        'video_bukti',
        'solusi_diajukan',
        'status',
        'keputusan_resolusi',
        'catatan_pemasok',
        'catatan_lkbb',
        'deadline_at',
        'riwayat_audit',
        'flag_fraud',
    ];

    protected $casts = [
        'foto_bukti'    => 'array',
        'riwayat_audit' => 'array',
        'deadline_at'   => 'datetime',
        'flag_fraud'    => 'boolean',
    ];

    // ============================================================
    // CONSTANTS (anti-hardcode di blade — gunakan PengajuanReturn::TYPES dll)
    // ============================================================

    public const TYPES = [
        'rusak'          => 'Barang Rusak / Cacat',
        'basi'           => 'Basi / Kedaluwarsa',
        'kurang_qty'     => 'Jumlah Kurang',
        'salah_barang'   => 'Salah Barang / Tidak Sesuai',
        'kualitas_buruk' => 'Kualitas Buruk',
        'terlambat'      => 'Terlambat / Tidak Tepat Waktu',
    ];

    public const SOLUTIONS = [
        'refund'         => 'Pengembalian Dana (Refund)',
        'kirim_ulang'    => 'Kirim Ulang Barang yang Sama',
        'ganti_barang'   => 'Ganti dengan Barang Lain',
        'partial_refund' => 'Refund Sebagian (Partial)',
    ];

    public const STATUSES = [
        'pending_supplier_review' => 'Menunggu Review Pemasok',
        'approved'                => 'Disetujui Pemasok',
        'rejected'                => 'Ditolak Pemasok',
        'escalated_lkbb'          => 'Eskalasi ke LKBB',
        'resolved'                => 'Diselesaikan LKBB',
    ];

    public const STATUS_COLORS = [
        'pending_supplier_review' => 'amber',
        'approved'                => 'emerald',
        'rejected'                => 'rose',
        'escalated_lkbb'          => 'purple',
        'resolved'                => 'blue',
    ];

    public const DEADLINE_HOURS = 48; // pemasok harus respond dalam 48 jam

    // ============================================================
    // RELATIONS
    // ============================================================

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'supply_order_id');
    }

    public function supplyOrderDetail(): BelongsTo
    {
        return $this->belongsTo(SupplyOrderDetail::class, 'supply_order_detail_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function appendAudit(string $actorRole, string $event, ?string $detail = null): void
    {
        $log = $this->riwayat_audit ?? [];
        $log[] = [
            'at'     => now()->toIso8601String(),
            'actor'  => $actorRole,
            'event'  => $event,
            'detail' => $detail,
        ];
        $this->riwayat_audit = $log;
        $this->save();
    }

    public function isExpired(): bool
    {
        return $this->deadline_at !== null
            && $this->status === 'pending_supplier_review'
            && now()->greaterThan($this->deadline_at);
    }

    public function canBeAppealed(): bool
    {
        return $this->status === 'rejected';
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['resolved'], true)
            || ($this->status === 'approved' && $this->keputusan_resolusi);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function tipeLabel(): string
    {
        return self::TYPES[$this->tipe_masalah] ?? ucfirst((string) $this->tipe_masalah);
    }

    public function solusiLabel(): string
    {
        return self::SOLUTIONS[$this->solusi_diajukan] ?? ucfirst((string) $this->solusi_diajukan);
    }
}
