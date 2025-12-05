<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "email",
        "name",
        "first_name",
        "last_name",
        "tanggal_lahir",
        "jenis_kelamin",
        "telephone",
        "nomor_identitas",
        "alamat",
        "jenis_identitas",
        "foto_profil",
        "status",
        "profile_complete",
        "otp",
        "otp_created_at",
        "otp_expires_at",
        "password",
        "role",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function pemesanan()
    {
        return $this->hasMany(Pemesanan::class);
    }
    public function invvoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
