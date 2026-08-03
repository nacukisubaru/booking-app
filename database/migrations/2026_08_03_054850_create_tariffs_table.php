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
        Schema::create('tariffs', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->float('price');
            $table->uuid('room_type_id')->nullable();
            $table->uuid('tariff_plan_id')->nullable();

            $table->index(['room_type_id', 'tariff_plan_id', 'date'], 'idx_tariffs_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
