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
        Schema::table('tariff_plans_services', function (Blueprint $table) {
            $table->foreign(['tariff_plan_id'], 'FK_550c9a561a1b5566696f05a90ef')->references(['id'])->on('tariff_plans')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['service_id'], 'FK_5753cd46e2e34890f0887d985ec')->references(['id'])->on('services')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariff_plans_services', function (Blueprint $table) {
            $table->dropForeign('FK_550c9a561a1b5566696f05a90ef');
            $table->dropForeign('FK_5753cd46e2e34890f0887d985ec');
        });
    }
};
