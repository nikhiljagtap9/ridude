<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | driver_commute_settings
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'driver_commute_settings',
            function (Blueprint $table) {

                $table->unsignedBigInteger(
                    'state_id'
                )
                ->nullable()
                ->after(
                    'home_longitude'
                );

                $table->unsignedBigInteger(
                    'city_id'
                )
                ->nullable()
                ->after(
                    'state_id'
                );


                $table->index(
                    'state_id'
                );

                $table->index(
                    'city_id'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | trips
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'trips',
            function (Blueprint $table) {

                $table->unsignedBigInteger(
                    'state_id'
                )
                ->nullable()
                ->after(
                    'driver_id'
                );

                $table->unsignedBigInteger(
                    'city_id'
                )
                ->nullable()
                ->after(
                    'state_id'
                );


                $table->index(
                    'state_id'
                );

                $table->index(
                    'city_id'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | trip_riders
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'trip_riders',
            function (Blueprint $table) {

                $table->unsignedBigInteger(
                    'state_id'
                )
                ->nullable()
                ->after(
                    'rider_id'
                );

                $table->unsignedBigInteger(
                    'city_id'
                )
                ->nullable()
                ->after(
                    'state_id'
                );


                $table->index(
                    'state_id'
                );

                $table->index(
                    'city_id'
                );
            }
        );
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | trip_riders
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'trip_riders',
            function (Blueprint $table) {

                $table->dropIndex([
                    'state_id'
                ]);

                $table->dropIndex([
                    'city_id'
                ]);


                $table->dropColumn([
                    'state_id',
                    'city_id',
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | trips
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'trips',
            function (Blueprint $table) {

                $table->dropIndex([
                    'state_id'
                ]);

                $table->dropIndex([
                    'city_id'
                ]);


                $table->dropColumn([
                    'state_id',
                    'city_id',
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | driver_commute_settings
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'driver_commute_settings',
            function (Blueprint $table) {

                $table->dropIndex([
                    'state_id'
                ]);

                $table->dropIndex([
                    'city_id'
                ]);


                $table->dropColumn([
                    'state_id',
                    'city_id',
                ]);
            }
        );
    }
};