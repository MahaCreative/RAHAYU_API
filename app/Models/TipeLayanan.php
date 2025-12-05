<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipeLayanan extends Model
{
    /** @use HasFactory<\Database\Factories\TipeLayananFactory> */
    use HasFactory;

    protected $fillable = [
        'tipe_layanan',
        'deskripsi_layanan',
    ];

    public function layanans(): HasMany
    {
        return $this->hasMany(Layanan::class, 'tipe_layanan_id');
    }
}
