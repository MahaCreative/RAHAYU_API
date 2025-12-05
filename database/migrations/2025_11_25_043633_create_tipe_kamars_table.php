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
        Schema::create('tipe_kamars', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tipe');
            $table->text('deskripsi_tipe')->nullable();
            $table->decimal('harga_per_malam', 10, 2);
            $table->integer('kapasitas_orang');
            $table->string('fasilitas_tipe')->nullable();
            $table->string('foto_tipe')->default('image/room.jpg');
            $table->integer('stok_kamar')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipe_kamars');
    }
};
