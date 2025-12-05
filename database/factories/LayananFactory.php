<?php

namespace Database\Factories;

use App\Models\TipeLayanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Layanan>
 */
class LayananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ambil salah satu tipe_layanan yang sudah ada
        $tipe = TipeLayanan::inRandomOrder()->first();

        // Jika tidak ada tipe layanan, buat dummy
        if (!$tipe) {
            $tipe = TipeLayanan::factory()->create();
        }

        return [
            'tipe_layanan_id' => $tipe->id,
            'nama_layanan' => match ($tipe->tipe_layanan) {
                'Kebersihan' => $this->faker->randomElement(['Cleaning Room', 'Deep Cleaning', 'Disinfection']),
                'Laundry' => $this->faker->randomElement(['Cuci Kering', 'Setrika', 'Laundry Express']),
                'Room Service' => $this->faker->randomElement(['Pesan Makanan', 'Pesan Minuman', 'Meal Package']),
                'Spa & Massage' => $this->faker->randomElement(['Full Body Massage', 'Foot Massage', 'Aromatherapy']),
                'Transportasi' => $this->faker->randomElement(['Airport Shuttle', 'City Tour', 'Rent Car']),
                default => $this->faker->words(2, true),
            },
            'deskripsi_layanan' => $this->faker->sentence(10),
            'harga_layanan' => $this->faker->randomFloat(2, 50000, 500000),
            'foto_layanan' => 'image/thumbnail_default.png',
        ];
    }
}
