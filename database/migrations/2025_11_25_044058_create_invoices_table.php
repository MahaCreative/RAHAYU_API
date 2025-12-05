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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('pemesanan_id')->constrained('pemesanans')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('order_id')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('jumlah_bayar', 10, 2);
            $table->string('payment_type')->nullable();
            $table->json('payment_info')->nullable();
            $table->string('payment_code')->nullable();
            $table->date('succeded_at')->nullable();
            $table->string('status_pembayaran')->default('pending'); // pending, settlement, cancelled
            $table->string('status_konfirmasi')->default('unconfirmed'); // unconfirmed, confirmed, rejected
            $table->date('waktu_konfirmasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
