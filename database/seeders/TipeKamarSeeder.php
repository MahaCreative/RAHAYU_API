<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipeKamarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipe_kamars')->insert([
            [
                'nama_tipe' => 'Standard Room',
                'deskripsi_tipe' => 'Kamar standar dengan fasilitas dasar dan kenyamanan terbaik.',
                'harga_per_malam' => 250000,
                'kapasitas_orang' => 2,
                'fasilitas_tipe' => 'WiFi, AC, TV, Kamar Mandi Dalam',
                'foto_tipe' => 'image/room.jpg',
                'stok_kamar' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_tipe' => 'Deluxe Room',
                'deskripsi_tipe' => 'Kamar deluxe dengan ruang lebih luas dan fasilitas modern.',
                'harga_per_malam' => 450000,
                'kapasitas_orang' => 3,
                'fasilitas_tipe' => 'WiFi, AC, TV, Air Panas, Sarapan',
                'foto_tipe' => 'image/room.jpg',
                'stok_kamar' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_tipe' => 'Superior Room',
                'deskripsi_tipe' => 'Kamar superior dengan kualitas premium dan kenyamanan ekstra.',
                'harga_per_malam' => 600000,
                'kapasitas_orang' => 3,
                'fasilitas_tipe' => 'WiFi, AC, TV, Air Panas, Room Service, Sarapan',
                'foto_tipe' => 'image/room.jpg',
                'stok_kamar' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_tipe' => 'Executive Suite',
                'deskripsi_tipe' => 'Suite mewah untuk pengalaman menginap maksimal.',
                'harga_per_malam' => 1200000,
                'kapasitas_orang' => 4,
                'fasilitas_tipe' => 'WiFi, AC, TV, Jacuzzi, Mini Bar, Room Service',
                'foto_tipe' => 'image/room.jpg',
                'stok_kamar' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_tipe' => 'Presidential Suite',
                'deskripsi_tipe' => 'Suite terbaik dengan fasilitas eksklusif dan layanan VIP.',
                'harga_per_malam' => 2500000,
                'kapasitas_orang' => 6,
                'fasilitas_tipe' => 'WiFi, AC, TV, Jacuzzi, Private Lounge, Room Service Lengkap',
                'foto_tipe' => 'image/room.jpg',
                'stok_kamar' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
