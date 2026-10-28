<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_riders', function (Blueprint $table) {

            /*
             * Before allocation rider has no trip.
             */
            $table->unsignedBigInteger('trip_id')
                ->nullable()
                ->change();


            /*
             * Rider requested date/direction.
             */
            $table->date('requested_trip_date')
                ->nullable()
                ->after('rider_id');

            $table->enum('requested_direction', [
                'home_to_work',
                'work_to_home',
            ])
            ->nullable()
            ->after('requested_trip_date');


            $table->index([
                'requested_trip_date',
                'requested_direction',
                'allocation_status'
            ], 'trip_riders_auto_allocation_index');
        });
    }


    public function down(): void
    {
        Schema::table('trip_riders', function (Blueprint $table) {

            $table->dropIndex(
                'trip_riders_auto_allocation_index'
            );

            $table->dropColumn([
                'requested_trip_date',
                'requested_direction',
            ]);
        });
    }
};