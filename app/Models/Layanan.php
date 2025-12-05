<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Layanan extends Model
{
    /** @use HasFactory<\Database\Factories\LayananFactory> */
    use HasFactory;

    protected $fillable = [
        'tipe_layanan_id',
        'nama_layanan',
        'deskripsi_layanan',
        'harga_layanan',
        'foto_layanan',

    ];

    public function tipeLayanan(): BelongsTo
    {
        return $this->belongsTo(TipeLayanan::class, 'tipe_layanan_id');
    }

    public function pemesananLayanans(): HasMany
    {
        return $this->hasMany(PemesananLayanan::class, 'layanan_id');
    }

    public function cartItems(): MorphMany
    {
        return $this->morphMany(CartItem::class, 'item');
    }
}
