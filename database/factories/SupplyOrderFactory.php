<?php

namespace Database\Factories;

use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplyOrder>
 */
class SupplyOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_order'       => 'SO-' . strtoupper($this->faker->unique()->bothify('??####')),
            'merchant_id'       => User::factory(),
            'pemasok_id'        => User::factory(),
            'total_estimasi'    => $this->faker->randomFloat(2, 50000, 5000000),
            'tanggal_kebutuhan' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'catatan'           => null,
            'status'            => 'menunggu_pemasok',
            'status_pembiayaan' => 'siap_diajukan',
            'id_pengajuan'      => null,
            'kurir'             => null,
            'nama_kurir'        => null,
            'no_hp_kurir'       => null,
            'no_resi'           => null,
            'tracking_history'  => null,
        ];
    }
}
