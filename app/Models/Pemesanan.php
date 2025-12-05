<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemesanan extends Model
{
    /** @use HasFactory<\Database\Factories\PemesananFactory> */
    use HasFactory;
    protected $fillable = [
        'user_id',
        'kode_pemesanan',
        'total_harga',
        'status_pemesanan',
        'waktu_pemesanan',
        'jumlah_bayar',
        'status_pembayaran'
    ];

    public function bookingKamars()
    {
        return $this->hasMany(\App\Models\BookingKamar::class, 'pemesanan_id');
    }

    public function pesananLayanans()
    {
        return $this->hasMany(\App\Models\PemesananLayanan::class, 'pemesanan_id');
    }

    public function invoice()
    {
        return $this->hasOne(\App\Models\invoice::class, 'pemesanan_id');
    }

    public function pembayarans()
    {
        return $this->hasMany(\App\Models\Pembayaran::class, 'pemesanan_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
