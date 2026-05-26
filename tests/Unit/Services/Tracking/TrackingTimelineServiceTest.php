<?php

use App\Models\SupplyOrder;
use App\Services\Tracking\TrackingTimelineService;
use Carbon\Carbon;

function makeOrder(array $attrs = []): SupplyOrder
{
    $order = new SupplyOrder();
    $order->status = $attrs['status'] ?? 'pending';
    $order->nama_kurir = $attrs['nama_kurir'] ?? null;
    $order->no_hp_kurir = $attrs['no_hp_kurir'] ?? null;
    $order->no_resi = $attrs['no_resi'] ?? null;
    $order->created_at = $attrs['created_at'] ?? Carbon::parse('2026-05-20 09:00:00');
    $order->updated_at = $attrs['updated_at'] ?? Carbon::parse('2026-05-21 14:30:00');
    return $order;
}

it('builds the canonical 4-step timeline', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'pending']));

    expect($events)->toHaveCount(4);
    expect($events->pluck('key')->all())->toBe([
        'pesanan_dibuat',
        'diproses_pemasok',
        'dikirim',
        'selesai',
    ]);
});

it('marks pesanan_dibuat active for a brand-new pending order', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'pending']));

    expect($events[0]['state'])->toBe('active');
    expect($events[1]['state'])->toBe('pending');
    expect($events[3]['state'])->toBe('pending');
});

it('marks every step completed for a selesai order', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'selesai']));

    foreach ($events as $event) {
        expect($event['state'])->toBe('completed');
    }
});

it('marks dikirim active and earlier steps completed when status is dikirim', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'dikirim']));

    expect($events[0]['state'])->toBe('completed');
    expect($events[1]['state'])->toBe('completed');
    expect($events[2]['state'])->toBe('active');
    expect($events[3]['state'])->toBe('pending');
});
