<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_riders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trip_id')
                ->constrained('trips')
                ->cascadeOnDelete();

            $table->foreignId('rider_id')
                ->constrained('users')
                ->cascadeOnDelete();


            /*
             * Rider Pickup
             */
            $table->string(
                'pickup_address',
                255
            );

            $table->decimal(
                'pickup_latitude',
                10,
                7
            )->nullable();

            $table->decimal(
                'pickup_longitude',
                10,
                7
            )->nullable();

            $table->time(
                'estimated_pickup_time'
            )->nullable();


            /*
             * Rider Dropoff
             */
            $table->string(
                'dropoff_address',
                255
            );

            $table->decimal(
                'dropoff_latitude',
                10,
                7
            )->nullable();

            $table->decimal(
                'dropoff_longitude',
                10,
                7
            )->nullable();


            /*
             * Route order
             */
            $table->unsignedTinyInteger(
                'stop_order'
            )->nullable();


            /*
             * Booking
             */
            $table->enum('booking_status', [
                'confirmed',
                'completed',
                'cancelled'
            ])->default('confirmed');


            /*
             * Trip rider status
             */
            $table->enum('ride_status', [
                'waiting',
                'picked_up',
                'dropped_off'
            ])->default('waiting');


            /*
             * Fare snapshot
             */
            $table->decimal(
                'fare_amount',
                10,
                2
            )->default(0);


            $table->timestamp(
                'confirmed_at'
            )->nullable();

            $table->timestamp(
                'picked_up_at'
            )->nullable();

            $table->timestamp(
                'dropped_off_at'
            )->nullable();

            $table->timestamp(
                'cancelled_at'
            )->nullable();


            /*
             * rider / driver / admin
             */
            $table->string(
                'cancelled_by',
                30
            )->nullable();

            $table->text(
                'cancel_reason'
            )->nullable();


            $table->timestamps();


            $table->unique([
                'trip_id',
                'rider_id'
            ], 'trip_rider_unique');


            $table->index([
                'rider_id',
                'booking_status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_riders');
    }
};