<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_riders', function (Blueprint $table) {

            $table->decimal('distance_miles', 8, 2)
                ->nullable()
                ->after('fare_amount');

            $table->unsignedTinyInteger('fare_level_no')
                ->nullable()
                ->after('distance_miles');

            $table->decimal('fare_base_price', 10, 2)
                ->nullable()
                ->after('fare_level_no');

            $table->decimal('fare_price_per_mile', 10, 2)
                ->nullable()
                ->after('fare_base_price');

            $table->enum('allocation_status', [
                'pending',
                'allocated',
                'finalized',
                'cancelled',
            ])
            ->default('pending')
            ->after('ride_status');

            $table->enum('allocation_type', [
                'auto',
                'manual',
            ])
            ->default('auto')
            ->after('allocation_status');

            $table->dateTime('allocated_at')
                ->nullable()
                ->after('allocation_type');

            $table->dateTime('finalized_at')
                ->nullable()
                ->after('allocated_at');

            $table->index('allocation_status');
        });
    }

    public function down(): void
    {
        Schema::table('trip_riders', function (Blueprint $table) {

            $table->dropIndex([
                'allocation_status'
            ]);

            $table->dropColumn([
                'distance_miles',
                'fare_level_no',
                'fare_base_price',
                'fare_price_per_mile',
                'allocation_status',
                'allocation_type',
                'allocated_at',
                'finalized_at',
            ]);
        });
    }
};