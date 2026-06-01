<?php

namespace App\Services\Lkbb;

use App\Models\SupplyOrder;

/**
 * Evaluasi kriteria pendanaan PO untuk approval LKBB.
 *
 * Semua kriteria di-evaluasi otomatis dari data registrasi, PO, dan
 * running balance tagihan_setoran_tunai merchant.
 *
 * Khusus "tunggakan": output state 3 level (safe/warning/blocked).
 * - safe    : tagihan = 0 → langsung lolos.
 * - warning : tagihan > 0 & tagihan < total PO → boleh approve dengan
 *             override manual approver.
 * - blocked : tagihan >= total PO → tidak boleh approve sama sekali.
 */
class PoValidationService
{
    public function evaluate(SupplyOrder $order): array
    {
        $merchant        = $order->merchant;
        $merchantProfile = $merchant?->merchantProfile;
        $pemasokProfile  = $order->pemasok?->pemasokProfile;

        return [
            'merchant_verified' => $this->evaluateMerchantVerified($merchantProfile),
            'supplier_verified' => $this->evaluateSupplierVerified($pemasokProfile),
            'rekening_valid'    => $this->evaluateRekeningValid($pemasokProfile),
            'po_complete'       => $this->evaluatePoComplete($order),
            'tunggakan'         => $this->evaluateTunggakan($merchantProfile, $order),
        ];
    }

    /**
     * Evaluasi tagihan_setoran_tunai merchant vs total PO.
     * Output: ['status' => safe|warning|blocked, 'amount' => float, 'po_total' => float, 'reason' => string]
     */
    private function evaluateTunggakan($profile, SupplyOrder $order): array
    {
        $tagihan = (float) ($profile?->tagihan_setoran_tunai ?? 0);
        $poTotal = (float) $order->total_estimasi;

        if ($tagihan <= 0) {
            return [
                'status'   => 'safe',
                'amount'   => 0.0,
                'po_total' => $poTotal,
                'reason'   => 'Merchant tidak memiliki tunggakan setoran aktif.',
            ];
        }

        if ($tagihan >= $poTotal) {
            return [
                'status'   => 'blocked',
                'amount'   => $tagihan,
                'po_total' => $poTotal,
                'reason'   => 'Tunggakan setoran merchant melebihi nilai PO. Pencairan diblokir hingga setoran dilunasi.',
            ];
        }

        return [
            'status'   => 'warning',
            'amount'   => $tagihan,
            'po_total' => $poTotal,
            'reason'   => 'Merchant masih punya tunggakan setoran namun di bawah nilai PO. Memerlukan override approver.',
        ];
    }

    private function evaluateMerchantVerified($profile): array
    {
        if (! $profile) {
            return $this->fail('Profil merchant belum dibuat.');
        }
        if ($profile->status_verifikasi !== 'disetujui') {
            $status = $profile->status_verifikasi ?: 'belum_melengkapi';
            return $this->fail("Status verifikasi merchant: {$status}.");
        }
        return $this->pass();
    }

    private function evaluateSupplierVerified($profile): array
    {
        if (! $profile) {
            return $this->fail('Profil pemasok belum dibuat.');
        }
        if ($profile->status_verifikasi !== 'disetujui') {
            $status = $profile->status_verifikasi ?: 'belum_melengkapi';
            return $this->fail("Pemasok belum diverifikasi LKBB (status: {$status}).");
        }
        if ($profile->status_kemitraan !== 'aktif') {
            return $this->fail('Status kemitraan pemasok nonaktif.');
        }
        return $this->pass();
    }

    private function evaluateRekeningValid($profile): array
    {
        if (! $profile) {
            return $this->fail('Profil pemasok belum dibuat.');
        }
        if (empty($profile->nama_bank) || empty($profile->no_rekening)) {
            return $this->fail('Rekening pemasok belum lengkap (bank/nomor).');
        }
        return $this->pass();
    }

    private function evaluatePoComplete(SupplyOrder $order): array
    {
        if ($order->details->count() < 1) {
            return $this->fail('PO tidak memiliki detail item.');
        }
        if ((float) $order->total_estimasi <= 0) {
            return $this->fail('Total estimasi PO tidak valid.');
        }
        if (! $order->tanggal_kebutuhan) {
            return $this->fail('Tanggal kebutuhan PO belum diisi.');
        }
        return $this->pass();
    }

    private function pass(): array
    {
        return ['passed' => true, 'reason' => null];
    }

    private function fail(string $reason): array
    {
        return ['passed' => false, 'reason' => $reason];
    }
}
