<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Kamar extends Model
{
    /** @use HasFactory<\Database\Factories\KamarFactory> */
    use HasFactory;

    protected $fillable = [
        "tipe_kamar_id",
        "nomor_kamar",
        "status_kamar",
        "lantai_kamar",
        "foto_kamar",
        "catatan_kamar",
        "harga_kamar",
        "kapasitas_kamar",
        "fasilitas_kamar",
        "kebijakan_kamar",
        "foto_lainnya"

    ];

    protected $casts = [
        'foto_lainnya' => 'array',
        'fasilitas_kamar' => 'array',
    ];

    public function tipeKamar()
    {
        return $this->belongsTo(TipeKamar::class, 'tipe_kamar_id');
    }

    public function bookingKamars()
    {
        return $this->belongsTo(BookingKamar::class);
    }

    public function cartItems(): MorphMany
    {
        return $this->morphMany(CartItem::class, 'item');
    }
}
