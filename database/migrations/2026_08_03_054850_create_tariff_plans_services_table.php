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
        Schema::create('tariff_plans_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('service_id')->nullable();
            $table->uuid('tariff_plan_id')->nullable();

            $table->unique(['tariff_plan_id', 'service_id']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('alter table tariff_plans_services alter column id set default gen_random_uuid()');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariff_plans_services');
    }
};
