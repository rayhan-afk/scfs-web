<?php

namespace App\Services\Lkbb;

use App\Models\SupplyOrder;

/**
 * Evaluasi kriteria pendanaan PO untuk approval LKBB.
 *
 * Lima kriteria di-evaluasi otomatis dari data registrasi & PO.
 * Kriteria "no_tunggakan" sengaja TIDAK di-handle di sini karena
 * masih melibatkan proses pembayaran tunai/offline yang belum
 * fully tracked oleh sistem — wajib di-checklist manual oleh
 * approver LKBB.
 */
class PoValidationService
{
    /**
     * Evaluasi seluruh kriteria otomatis untuk sebuah PO.
     *
     * Return array dengan struktur:
     *   [
     *     'merchant_verified' => ['passed' => bool, 'reason' => string|null],
     *     'supplier_verified' => [...],
     *     'rekening_valid'    => [...],
     *     'po_complete'       => [...],
     *   ]
     */
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
