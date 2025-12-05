<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profile_hotels', function (Blueprint $table) {
            $table->id();
            $table->string('nama_hotel');
            $table->string('subtitle')->nullable();
            $table->string('alamat_hotel');
            $table->string('nomor_telepon');
            $table->string('email_hotel')->unique();
            $table->text('deskripsi_hotel')->nullable();
            $table->string('logo_hotel')->nullable();
            $table->string('foto_hotel')->nullable();
            $table->string('fasilitas')->nullable();
            $table->string('kebijakan_hotel')->nullable();
            $table->string('jam_check_in')->nullable();
            $table->string('jam_check_out')->nullable();
            $table->string('foto_lainnya')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_hotels');
    }
};
