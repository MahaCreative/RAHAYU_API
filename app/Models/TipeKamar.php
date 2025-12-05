<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipeKamar extends Model
{
    /** @use HasFactory<\Database\Factories\TipeKamarFactory> */
    use HasFactory;

    protected $fillable = [
        'nama_tipe',
        'deskripsi_tipe',
        'harga_per_malam',
        'kapasitas_orang',
        'fasilitas_tipe',
        'foto_tipe',
        'stok_kamar',
    ];

    public function kamars(): HasMany
    {
        return $this->hasMany(Kamar::class, 'tipe_kamar_id');
    }
}
