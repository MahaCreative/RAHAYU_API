<?php

namespace Database\Seeders;

use App\Models\Kamar;
use App\Models\Layanan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::create([
            "email" => 'admin@gmail.com',
            "name" => 'admin lagi',
            "first_name" => 'admin lagi',
            "last_name" => 'lagi',
            "tanggal_lahir" => '1008-01-17',
            "jenis_kelamin" => 'laki-laki',
            "telephone" => '082352310844',
            "nomor_identitas" => '7306071701980005',
            "alamat" => 'jl. diponegoro no. 45',
            "jenis_identitas" => 'ktp',
            "status" => 'active',
            "profile_complete" => 'yes',
            "password" => bcrypt('password'),
            "role" => 'admin',
        ]);
        $this->call([
            ProfileHotelSeeder::class,
        ]);
    }
}
