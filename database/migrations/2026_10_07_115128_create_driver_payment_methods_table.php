<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_payment_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'user_id',
                'payment_method_id'
            ], 'driver_payment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_payment_methods');
    }
};