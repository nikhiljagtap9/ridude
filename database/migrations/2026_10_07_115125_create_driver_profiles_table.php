<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date_of_birth');

            $table->enum('gender', [
                'male',
                'female',
                'other'
            ]);

            $table->string('address');

            $table->string('city', 100);

            $table->string('state', 100);

            $table->string('zip_code', 20);

            $table->string('profile_photo')
                ->nullable();

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};