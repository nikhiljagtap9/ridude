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
        Schema::table('trip_riders', function (Blueprint $table) {

            $table->decimal(
                'pickup_distance_from_route',
                8,
                2
            )
            ->nullable()
            ->after('distance_miles');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_riders', function (Blueprint $table) {
            //
        });
    }
};
