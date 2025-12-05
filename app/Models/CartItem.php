<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'item_id',
        'item_type',
        'jumlah',
        'harga_satuan',
        'total_harga',
        'tanggal_checkin',
        'tanggal_checkout',
        'checked',
    ];

    protected $casts = [
        'checked' => 'boolean',
        'tanggal_checkin' => 'date',
        'tanggal_checkout' => 'date',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function item()
    {
        return $this->morphTo();
    }

    // Accessor to get simple type name: 'kamar' or 'layanan'
    public function getItemTypeNameAttribute()
    {
        if (! $this->item_type) return null;
        return strtolower(class_basename($this->item_type));
    }
}
