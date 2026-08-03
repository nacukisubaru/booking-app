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
        Schema::table('occupancies', function (Blueprint $table) {
            $table->foreign(['room_type_id'], 'FK_d85b398c79a1f68587a7a777eb0')->references(['id'])->on('room_types')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupancies', function (Blueprint $table) {
            $table->dropForeign('FK_d85b398c79a1f68587a7a777eb0');
        });
    }
};
