<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fare_levels', function (Blueprint $table) {

            $table->id();

            $table->unsignedTinyInteger('level_no')
                ->unique();

            $table->string('level_name', 50);

            $table->decimal('min_miles', 8, 2);

            $table->decimal('max_miles', 8, 2)
                ->nullable();

            $table->decimal('base_price', 10, 2)
                ->default(0);

            $table->decimal('price_per_mile', 10, 2)
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_levels');
    }
};