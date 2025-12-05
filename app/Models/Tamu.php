<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tamu extends Model
{
    use HasFactory;

    protected $table = 'tamus';

    protected $fillable = [
        'nama',
        'nik',
        'jenis_kelamin',
        'jenis_identitas',
        'kamar_id',
        'booking_kamar_id',
    ];

    public function bookingKamar(): BelongsTo
    {
        return $this->belongsTo(BookingKamar::class, 'booking_kamar_id');
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class, 'kamar_id');
    }
}
