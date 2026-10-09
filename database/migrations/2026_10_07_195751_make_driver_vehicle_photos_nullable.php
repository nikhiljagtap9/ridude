<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_vehicles', function (Blueprint $table) {
            $table->string('front_photo')->nullable()->change();
            $table->string('back_photo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('driver_vehicles', function (Blueprint $table) {
            $table->string('front_photo')->nullable(false)->change();
            $table->string('back_photo')->nullable(false)->change();
        });
    }
};