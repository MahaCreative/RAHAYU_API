<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemesananLayanan extends Model
{
    /** @use HasFactory<\Database\Factories\PemesananLayananFactory> */
    use HasFactory;

    protected $fillable = [
        'pemesanan_id',
        'layanan_id',
        'jumlah',
        'catatan',
        'total_harga'
    ];

    public function pemesanan(): BelongsTo
    {
        return $this->belongsTo(Pemesanan::class, 'pemesanan_id');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class, 'layanan_id');
    }
}
