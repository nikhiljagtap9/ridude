<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_commute_settings', function (Blueprint $table) {

            $table->time('shift_start_time')
                ->nullable()
                ->change();

            $table->time('shift_end_time')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('driver_commute_settings', function (Blueprint $table) {

            $table->time('shift_start_time')
                ->nullable(false)
                ->change();

            $table->time('shift_end_time')
                ->nullable(false)
                ->change();
        });
    }
};