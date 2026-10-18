<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_commute_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Home
             */
            $table->string('home_address', 255);

            $table->decimal(
                'home_latitude',
                10,
                7
            )->nullable();

            $table->decimal(
                'home_longitude',
                10,
                7
            )->nullable();


            /*
             * Workplace
             */
            $table->string(
                'workplace_name',
                150
            )->nullable();

            $table->string(
                'workplace_address',
                255
            );

            $table->decimal(
                'workplace_latitude',
                10,
                7
            )->nullable();

            $table->decimal(
                'workplace_longitude',
                10,
                7
            )->nullable();


            /*
             * Selected Trip Date
             */
            $table->date('commute_date');


            /*
             * Work Shift
             */
            $table->time('shift_start_time');

            $table->time('shift_end_time');


            /*
             * Driver Seats
             */
            $table->unsignedTinyInteger(
                'available_seats'
            )->default(3);


            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'user_id',
                'commute_date'
            ], 'driver_commute_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'driver_commute_settings'
        );
    }
};