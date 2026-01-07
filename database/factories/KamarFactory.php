<?php

namespace Database\Factories;

use App\Models\Kamar;
use App\Models\TipeKamar;
use Illuminate\Database\Eloquent\Factories\Factory;

class KamarFactory extends Factory
{
    protected $model = Kamar::class;

    public function definition(): array
    {
        $tipe = TipeKamar::inRandomOrder()->first()
            ?? TipeKamar::factory()->create();

        return [
            'tipe_kamar_id' => $tipe->id,

            'nomor_kamar' => $this->faker->unique()->numberBetween(1000, 9999),

            'status_kamar' => $this->faker->randomElement([
                'tersedia',
                'tidak tersedia',
                'dibooking',
                'dipakai'
            ]),

            'lantai_kamar' => $this->faker->numberBetween(1, 10),

            'foto_kamar' => 'image/room.jpg',

            'catatan_kamar' => $this->faker->optional()->sentence(),

            'harga_kamar' => $this->faker->randomFloat(2, 200000, 2000000),

            'kapasitas_kamar' => $this->faker->numberBetween(1, 6),

            'fasilitas_kamar' => implode(', ', $this->faker->randomElements([
                'WiFi',
                'AC',
                'TV',
                'Air Panas',
                'Sarapan',
                'Room Service'
            ], rand(2, 5))),

            'kebijakan_kamar' => $this->faker->optional()->sentence(5),

            'foto_lainnya' => json_encode([
                'image/sample1.jpg',
                'image/sample2.jpg',
                'image/sample3.jpg',
            ]),
        ];
    }
}
