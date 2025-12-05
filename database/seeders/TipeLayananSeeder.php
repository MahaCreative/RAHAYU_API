<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipeLayananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipe_layanans')->insert([
            [
                'tipe_layanan' => 'Kebersihan',
                'deskripsi_layanan' => 'Layanan kebersihan kamar dan area hotel secara rutin.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipe_layanan' => 'Laundry',
                'deskripsi_layanan' => 'Jasa cuci pakaian untuk tamu hotel dengan proses cepat.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipe_layanan' => 'Room Service',
                'deskripsi_layanan' => 'Pelayanan makanan dan minuman langsung ke kamar tamu.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipe_layanan' => 'Spa & Massage',
                'deskripsi_layanan' => 'Layanan relaksasi, pijat, dan terapi spa profesional.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipe_layanan' => 'Transportasi',
                'deskripsi_layanan' => 'Layanan antar jemput bandara dan transport lokal lainnya.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
