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
        Schema::create('occupancies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('adults');
            $table->integer('childrens');
            $table->boolean('extra_bed');
            $table->uuid('room_type_id')->nullable();

            $table->index('room_type_id');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('alter table occupancies alter column id set default gen_random_uuid()');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occupancies');
    }
};
