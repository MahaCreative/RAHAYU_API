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
        Schema::create('layanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipe_layanan_id')->constrained('tipe_layanans')->onDelete('cascade');
            $table->string('nama_layanan');
            $table->text('deskripsi_layanan')->nullable();
            $table->decimal('harga_layanan', 10, 2);
            $table->string('foto_layanan')->default('image/thumbnail_default.png');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanans');
    }
};
