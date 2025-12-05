<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: Changing existing columns requires the doctrine/dbal package for
     * the `change()` method to work. If you don't have it installed run:
     * `composer require doctrine/dbal` before executing `php artisan migrate`.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // make petugas_id nullable and change onDelete to set null
            // This uses change() which may require doctrine/dbal
            $table->unsignedBigInteger('petugas_id')->nullable()->change();
        });

        // re-add foreign key behaviour to set null on delete (do in raw SQL if needed)
        // Some DB engines (sqlite) may need manual foreign key recreation after change().
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('petugas_id')->nullable(false)->change();
        });
    }
};
