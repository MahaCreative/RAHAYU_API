<?php

namespace Database\Factories;

use App\Models\Kamar;
use App\Models\TipeKamar;
use Illuminate\Database\Eloquent\Factories\Factory;
use function Illuminate\Support\fake; // ✅ BENAR

class KamarFactory extends Factory
{
    protected $model = Kamar::class;

    public function definition(): array
    {
        $tipe = TipeKamar::inRandomOrder()->first()
            ?? TipeKamar::factory()->create();

        return [
            'tipe_kamar_id' => $tipe->id,

            'nomor_kamar' => fake()->unique()->numberBetween(1000, 9999),

            'status_kamar' => fake()->randomElement([
                'tersedia',
                'tidak tersedia',
                'dibooking',
                'dipakai',
            ]),

            'lantai_kamar' => fake()->numberBetween(1, 10),

            'foto_kamar' => 'image/room.jpg',

            'catatan_kamar' => fake()->optional()->sentence(),

            'harga_kamar' => fake()->randomFloat(2, 200000, 2000000),

            'kapasitas_kamar' => fake()->numberBetween(1, 6),

            'fasilitas_kamar' => implode(', ', fake()->randomElements([
                'WiFi',
                'AC',
                'TV',
                'Air Panas',
                'Sarapan',
                'Room Service',
            ], rand(2, 5))),

            'kebijakan_kamar' => fake()->optional()->sentence(5),

            'foto_lainnya' => [
                'image/sample1.jpg',
                'image/sample2.jpg',
                'image/sample3.jpg',
            ],
        ];
    }
}
