<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('max_adults');
            $table->integer('max_childrens');
            $table->integer('total_guests');
            $table->boolean('is_archive')->default(false);
            $table->uuid('hotel_id')->nullable();

            $table->index('hotel_id', 'idx_room_types_hotel');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('alter table room_types alter column id set default gen_random_uuid()');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
