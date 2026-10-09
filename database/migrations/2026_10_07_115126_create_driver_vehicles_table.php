<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_vehicles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('car_make', 100);

            $table->string('car_model', 100);

            $table->unsignedSmallInteger('car_year');

            $table->string('car_color', 50);

            $table->string('license_plate_number', 50)
                ->unique();

            $table->string('front_photo');

            $table->string('back_photo');

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_vehicles');
    }
};