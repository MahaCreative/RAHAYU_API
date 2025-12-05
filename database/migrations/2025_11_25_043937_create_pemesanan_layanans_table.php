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
        Schema::create('pemesanan_layanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('pemesanan_id')->nullable()->constrained('pemesanans')->onDelete('set null');
            $table->integer('jumlah')->default(1);
            $table->decimal('total_harga', 10, 2);
            $table->string('status_pemesanan')->default('pending'); // pending, completed, canceled
            $table->dateTime('waktu_pemesanan')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->dateTime('waktu_batal')->nullable();
            $table->text('catatan_pemesanan')->nullable();
            $table->foreignId('layanan_id')->constrained('layanans')->onDelete('cascade');
            $table->decimal('harga_satuan', 10, 2);
            $table->decimal('jumlah_bayar', 10, 2)->nullable();
            $table->decimal('sisa_bayar', 10, 2)->nullable();
            $table->dateTime('tanggal_bayar')->nullable();
            $table->string('status_pembayaran')->default('belum lunas'); // BELUM LUNAS, LUNAS
            $table->string('status_konfirmasi')->default('unconfirmed'); // unconfirmed, confirmed, rejected
            $table->dateTime('waktu_konfirmasi')->nullable();
            $table->dateTime('waktu_dibatalkan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemesanan_layanans');
    }
};
