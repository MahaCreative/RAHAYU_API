<?php

namespace Database\Seeders;

use App\Models\ProfileHotel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileHotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProfileHotel::create([
            'nama_hotel' => 'shanum hotel',
            'subtitle' => 'menginap aman dan nyaman dengan privasi terjamin',
            'alamat_hotel' => 'jl. anu anu anu',
            'nomor_telepon' => '085334703299',
            'email_hotel' => 'shanumhotel@gmail.com',
            'logo_hotel' => 'images/logo_hotel.png',
            'foto_hotel' => 'image/hoyrl.jpg',
            'fasilitas' => 'kosong',
            'kebijakan_hotel' => fake()->text,
        ]);
    }
}
