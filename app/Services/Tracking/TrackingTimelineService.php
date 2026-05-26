<?php

namespace App\Services\Tracking;

use App\Models\SupplyOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrackingTimelineService
{
    /**
     * Definisi 4 etape utama pelacakan supply order.
     * Urutan ini menentukan posisi (1..4) yang dipakai untuk derive state.
     */
    private const FLOW = [
        [
            'key' => 'pesanan_dibuat',
            'label' => 'Pesanan Dibuat',
            'description' => 'Merchant mengajukan kebutuhan supply ke sistem SCFS.',
            'icon' => 'shopping-bag',
        ],
        [
            'key' => 'diproses_pemasok',
            'label' => 'Disiapkan Pemasok',
            'description' => 'Pemasok mengemas barang & menyiapkan armada.',
            'icon' => 'package',
        ],
        [
            'key' => 'dikirim',
            'label' => 'Dalam Perjalanan',
            'description' => 'Kurir membawa barang menuju lokasi merchant.',
            'icon' => 'truck',
        ],
        [
            'key' => 'selesai',
            'label' => 'Diterima Merchant',
            'description' => 'Barang sampai dan dikonfirmasi oleh merchant.',
            'icon' => 'check-circle',
        ],
    ];

    public function buildEvents(SupplyOrder $order): Collection
    {
        $currentPosition = $this->positionForStatus($order->status);
        $isTerminated = $order->status === 'ditolak';

        return collect(self::FLOW)->map(function (array $step, int $index) use ($order, $currentPosition, $isTerminated) {
            $position = $index + 1;
            $state = $this->stateFor($position, $currentPosition, $order->status, $isTerminated);

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'description' => $step['description'],
                'icon' => $step['icon'],
                'timestamp' => $this->timestampFor($order, $position, $currentPosition),
                'state' => $state,
                'meta' => $this->metaFor($order, $step['key']),
            ];
        });
    }

    private function positionForStatus(string $status): int
    {
        return match ($status) {
            'pending', 'menunggu_lkbb', 'menunggu_pemasok' => 1,
            'diproses_pemasok' => 2,
            'dikirim' => 3,
            'selesai' => 4,
            default => 1,
        };
    }

    private function stateFor(int $position, int $currentPosition, string $status, bool $isTerminated): string
    {
        if ($isTerminated) {
            return 'pending';
        }
        if ($status === 'selesai') {
            return 'completed';
        }
        if ($position < $currentPosition) {
            return 'completed';
        }
        if ($position === $currentPosition) {
            return 'active';
        }
        return 'pending';
    }

    private function timestampFor(SupplyOrder $order, int $position, int $currentPosition): ?Carbon
    {
        if ($position > $currentPosition) {
            return null;
        }
        if ($position === 1) {
            return $order->created_at;
        }
        if ($position === $currentPosition) {
            return $order->updated_at;
        }
        return null;
    }

    private function metaFor(SupplyOrder $order, string $key): array
    {
        if ($key !== 'dikirim') {
            return [];
        }
        return array_filter([
            'kurir' => $order->nama_kurir,
            'hp' => $order->no_hp_kurir,
            'resi' => $order->no_resi,
        ], fn ($v) => filled($v));
    }

    public function progressPercentage(SupplyOrder $order): int
    {
        if ($order->status === 'ditolak') {
            return 0;
        }
        if ($order->status === 'selesai') {
            return 100;
        }
        $position = $this->positionForStatus($order->status);
        // Step aktif ditampilkan setengah-jalan untuk feedback visual.
        return (int) floor((($position - 0.5) / count(self::FLOW)) * 100);
    }

    public static function normalizeWhatsappUrl(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }
        return 'https://wa.me/' . $digits;
    }
}
