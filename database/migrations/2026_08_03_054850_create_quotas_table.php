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
        Schema::create('quotas', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->integer('count');
            $table->uuid('room_type_id')->nullable();

            $table->index(['room_type_id', 'date'], 'idx_quotas_lookup');
        });

        // PostgreSQL partial index to speed up availability lookups for sold-out days.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('create index idx_quotas_room_date_count on quotas (room_type_id, date) where count < 1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('drop index if exists idx_quotas_room_date_count');
        }

        Schema::dropIfExists('quotas');
    }
};
