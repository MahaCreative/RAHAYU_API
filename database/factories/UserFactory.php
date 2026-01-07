<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        return [
            'email' => $this->faker->unique->email(),

            // Nama Lengkap
            'name' => $firstName . ' ' . $lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,

            // Data pribadi
            'tanggal_lahir' => $this->faker->date('Y-m-d', '2005-01-01'),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'telephone' => $this->faker->numerify('08##########'),

            // Identitas
            'nomor_identitas' => $this->faker->numerify('################'),
            'jenis_identitas' => $this->faker->randomElement(['KTP', 'SIM', 'PASSPORT']),
            'alamat' => $this->faker->address(),

            // Profil
            'foto_profil' => 'image/profile.png',
            'status' => 'active',
            'profile_complete' => $this->faker->randomElement(['yes', 'no']),

            // OTP
            'otp' => null,
            'otp_created_at' => null,
            'otp_expires_at' => null,

            // Security
            'password' => Hash::make('password123'),
            'role' => 'costumer',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
