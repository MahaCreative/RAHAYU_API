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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');

            // POLYMORPHIC: item bisa kamar atau layanan
            $table->morphs('item');
            // menghasilkan item_id dan item_type

            $table->integer('jumlah')->default(1);
            $table->decimal('harga_satuan', 10, 2)->nullable(); // ini kalau itemnya adalah booking kamar maka jadi harga per malam
            $table->decimal('total_harga', 10, 2)->nullable(); // ini kalau itemnya adalah booking kamar maka jadi lama inap yah * harga
            $table->date('tanggal_checkin'); // ini berlaku ketika itemnya adalah booking kamar
            $table->date('tanggal_checkout'); // ini berlaku ketika itemnya adalah booking kamar
            $table->boolean('checked')->default(false); // <— untuk dichecklist
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
