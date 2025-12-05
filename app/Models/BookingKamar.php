<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingKamar extends Model
{
    /** @use HasFactory<\Database\Factories\BookingKamarFactory> */
    use HasFactory;

    protected $fillable = [
        'kode_booking',
        'pemesanan_id',
        'kamar_id',
        'petugas_id',
        'tanggal_checkin',
        'tanggal_checkout',
        'jumlah_tamu',
        'total_harga',
        'status_booking',
        'catatan_booking',
        'waktu_booking',
        'waktu_checkin',
        'waktu_checkout',
        'metode_pembayaran',
        'jumlah_bayar',
        'sisa_bayar',
        'bukti_pembayaran',
        'status_pembayaran',
        'status_konfirmasi',
    ];

    public function pemesanan(): BelongsTo
    {
        return $this->belongsTo(Pemesanan::class, 'pemesanan_id');
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class, 'kamar_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function tamu()
    {
        return $this->hasMany(Tamu::class, 'booking_kamar_id');
    }
}
