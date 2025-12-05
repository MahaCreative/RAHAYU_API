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
        Schema::create('pemesanans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pemesanan')->unique()->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_harga', 10, 2);
            $table->string('status_pemesanan')->default('pending'); // pending, confirmed, cancelled / done
            $table->text('catatan_pemesanan')->nullable();
            $table->dateTime('waktu_pemesanan')->nullable();
            $table->dateTime('waktu_konfirmasi')->nullable();
            $table->dateTime('waktu_batal')->nullable();
            $table->decimal('jumlah_bayar', 10, 2)->nullable();
            $table->decimal('sisa_bayar', 10, 2)->nullable();
            $table->string('bukti_pembayaran')->nullable();
            $table->string('status_pembayaran')->default('belum lunas'); // LUNAS / BELUM LUNAS
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemesanans');
    }
};
