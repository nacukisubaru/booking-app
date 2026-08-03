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
        // Ensure the native PostgreSQL enum type exists before it is used.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                DO $$
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'orders_status_enum') THEN
                        CREATE TYPE orders_status_enum AS ENUM ('pending', 'confirmed', 'cancelled');
                    END IF;
                END
                $$;
            SQL);
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('check_in_date');
            $table->timestamp('check_out_date');
            $table->integer('reserved_adults');
            $table->integer('reserved_childrens');
            $table->jsonb('daily_prices');
            $table->float('total_price');
            $table->timestamp('created_at');
            $table->uuid('roomTypeId')->nullable();

            $table->index('roomTypeId');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('alter table orders alter column id set default gen_random_uuid()');
            DB::statement('alter table orders alter column created_at set default now()');
        }

        // The production schema uses a native PostgreSQL enum type for the status column.
        DB::statement("alter table \"orders\" add column \"status\" orders_status_enum not null default 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS orders_status_enum');
        }
    }
};
