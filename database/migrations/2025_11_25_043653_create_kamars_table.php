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
        Schema::create('kamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipe_kamar_id')->constrained('tipe_kamars')->onDelete('cascade');
            $table->string('nomor_kamar')->unique();
            $table->string('status_kamar')->default('tersedia'); // tersedia, tidak tersedia, dibooking, dipakai, cleaning, maintenance
            $table->string('lantai_kamar')->nullable();
            $table->string('foto_kamar')->default('image/room.jpg');
            $table->text('catatan_kamar')->nullable();
            $table->decimal('harga_kamar', 10, 2)->nullable();
            $table->integer('kapasitas_kamar')->nullable();
            $table->string('fasilitas_kamar')->nullable();
            $table->string('kebijakan_kamar')->nullable();
            $table->json('foto_lainnya')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamars');
    }
};
