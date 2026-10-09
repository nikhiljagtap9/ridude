<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            $table->string('slug', 100)->unique();

            $table->decimal('price', 10, 2);

            $table->enum('billing_cycle', [
                'week',
                'month'
            ]);

            $table->unsignedInteger('trial_days')
                ->default(7);

            $table->string('currency', 10)
                ->default('USD');

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
