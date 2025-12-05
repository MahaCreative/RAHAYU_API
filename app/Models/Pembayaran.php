<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\PembayaranFactory> */
    use HasFactory;
    protected $fillable = ['pemesanan_id', 'va_number', 'bank', 'total', 'status', 'expiry', 'order_id'];

    public function pemesanan()
    {
        return $this->belongsTo(\App\Models\Pemesanan::class, 'pemesanan_id');
    }
}
