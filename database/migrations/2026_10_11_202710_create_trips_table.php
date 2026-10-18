<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_id')
                ->constrained('users')
                ->cascadeOnDelete();


            /*
             * home_to_work
             * work_to_home
             */
            $table->enum('direction', [
                'home_to_work',
                'work_to_home'
            ]);


            $table->date('trip_date');

            $table->time('departure_time');


            /*
             * Start
             */
            $table->string(
                'start_name',
                150
            )->nullable();

            $table->string(
                'start_address',
                255
            );

            $table->decimal(
                'start_latitude',
                10,
                7
            )->nullable();

            $table->decimal(
                'start_longitude',
                10,
                7
            )->nullable();


            /*
             * Destination
             */
            $table->string(
                'destination_name',
                150
            )->nullable();

            $table->string(
                'destination_address',
                255
            );

            $table->decimal(
                'destination_latitude',
                10,
                7
            )->nullable();

            $table->decimal(
                'destination_longitude',
                10,
                7
            )->nullable();


            /*
             * Seats
             */
            $table->unsignedTinyInteger(
                'available_seats'
            )->default(3);


            /*
             * Fare
             */
            $table->decimal(
                'fare_per_rider',
                10,
                2
            )->default(0);


            /*
             * Route Information
             */
            $table->decimal(
                'estimated_distance',
                10,
                2
            )->nullable();

            $table->unsignedInteger(
                'estimated_duration_minutes'
            )->nullable();


            /*
             * Actual final distance
             */
            $table->decimal(
                'actual_distance',
                10,
                2
            )->nullable();


            /*
             * scheduled
             * active
             * completed
             * cancelled
             */
            $table->enum('status', [
                'scheduled',
                'active',
                'completed',
                'cancelled'
            ])->default('scheduled');


            $table->timestamp('started_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('cancel_reason')
                ->nullable();


            $table->timestamps();


            /*
             * One driver should have only one
             * Home->Work and Work->Home trip per date
             */
            $table->unique([
                'driver_id',
                'trip_date',
                'direction'
            ], 'driver_trip_direction_unique');


            $table->index([
                'trip_date',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};