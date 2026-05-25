<?php

use App\Livewire\Pemasok\PengirimanLogistik;
use App\Models\SupplyOrder;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('validates nama_kurir is required', function () {
    $pemasok = User::factory()->create();
    $order = SupplyOrder::factory()->create([
        'pemasok_id' => $pemasok->id,
        'status' => 'diproses_pemasok',
    ]);

    actingAs($pemasok);

    Livewire::test(PengirimanLogistik::class)
        ->call('bukaModalAtur', $order->id)
        ->set('nama_kurir', '')
        ->set('no_hp_kurir', '081234567890')
        ->call('simpanPengiriman')
        ->assertHasErrors(['nama_kurir' => 'required']);
});

it('validates no_hp_kurir must be 10-15 digits', function () {
    $pemasok = User::factory()->create();
    $order = SupplyOrder::factory()->create([
        'pemasok_id' => $pemasok->id,
        'status' => 'diproses_pemasok',
    ]);

    actingAs($pemasok);

    Livewire::test(PengirimanLogistik::class)
        ->call('bukaModalAtur', $order->id)
        ->set('nama_kurir', 'Budi')
        ->set('no_hp_kurir', '123')
        ->call('simpanPengiriman')
        ->assertHasErrors(['no_hp_kurir']);
});

it('persists kurir info to dedicated columns and changes status to dikirim', function () {
    $pemasok = User::factory()->create();
    $order = SupplyOrder::factory()->create([
        'pemasok_id' => $pemasok->id,
        'status' => 'diproses_pemasok',
        'catatan' => 'Catatan asli dari merchant',
    ]);

    actingAs($pemasok);

    Livewire::test(PengirimanLogistik::class)
        ->call('bukaModalAtur', $order->id)
        ->set('nama_kurir', 'Budi Santoso')
        ->set('no_hp_kurir', '081234567890')
        ->call('simpanPengiriman')
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe('dikirim');
    expect($order->nama_kurir)->toBe('Budi Santoso');
    expect($order->no_hp_kurir)->toBe('081234567890');
    expect($order->no_resi)->toStartWith('SCFS-');
    expect($order->catatan)->toBe('Catatan asli dari merchant');
});
