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
        Schema::table('tariff_plans', function (Blueprint $table) {
            $table->foreign(['hotel_id'], 'FK_1ff66853f505b2ca00f060736fc')->references(['id'])->on('hotels')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariff_plans', function (Blueprint $table) {
            $table->dropForeign('FK_1ff66853f505b2ca00f060736fc');
        });
    }
};
