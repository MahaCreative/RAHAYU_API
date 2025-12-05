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
        Schema::create('booking_kamars', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking')->unique()->nullable();
            $table->foreignId('pemesanan_id')->nullable()->constrained('pemesanans')->onDelete('set null');
            $table->foreignId('kamar_id')->constrained('kamars')->onDelete('cascade');
            $table->foreignId('petugas_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('tanggal_checkin');
            $table->date('tanggal_checkout');
            $table->integer('jumlah_tamu');
            $table->decimal('total_harga', 10, 2)->nullable();
            $table->string('status_booking')->default('pending'); // pending, confirmed, proses, canceled
            $table->text('catatan_booking')->nullable();
            $table->dateTime('waktu_booking')->nullable();
            $table->dateTime('waktu_checkin')->nullable();
            $table->dateTime('waktu_checkout')->nullable();
            $table->decimal('jumlah_bayar', 10, 2)->nullable();
            $table->decimal('sisa_bayar', 10, 2)->nullable();
            $table->string('status_pembayaran')->default('belum lunas'); // BELUM LUNAS, LUNAS
            $table->string('status_konfirmasi')->default('unconfirmed'); // unconfirmed, confirmed, rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_kamars');
    }
};
