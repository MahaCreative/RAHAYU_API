<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileHotel extends Model
{
    protected $fillable = [
        'nama_hotel',
        'subtitle',
        'alamat_hotel',
        'nomor_telepon',
        'email_hotel',
        'deskripsi_hotel',
        'logo_hotel',
        'foto_hotel',
        'fasilitas',
        'kebijakan_hotel',
        'jam_check_in',
        'jam_check_out',
        'foto_lainnya',
    ];
}
