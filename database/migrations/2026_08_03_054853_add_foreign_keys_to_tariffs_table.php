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
        Schema::table('tariffs', function (Blueprint $table) {
            $table->foreign(['tariff_plan_id'], 'FK_5ac77c4fd65e101a6abdcf0e9c0')->references(['id'])->on('tariff_plans')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['room_type_id'], 'FK_a23c9bd01530fbe5122eef13ed2')->references(['id'])->on('room_types')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropForeign('FK_5ac77c4fd65e101a6abdcf0e9c0');
            $table->dropForeign('FK_a23c9bd01530fbe5122eef13ed2');
        });
    }
};
