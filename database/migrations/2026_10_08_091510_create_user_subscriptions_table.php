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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->cascadeOnDelete();

            $table->decimal('price', 10, 2);

            $table->string('currency', 10)
                ->default('USD');

            $table->enum('billing_cycle', [
                'week',
                'month'
            ]);

            $table->timestamp('trial_start_at')
                ->nullable();

            $table->timestamp('trial_end_at')
                ->nullable();

            $table->timestamp('subscription_start_at')
                ->nullable();

            $table->timestamp('subscription_end_at')
                ->nullable();

            $table->enum('status', [
                'trial',
                'active',
                'cancelled',
                'expired'
            ])->default('trial');

            $table->boolean('auto_renew')
                ->default(true);

            $table->string('store')
                ->nullable();

            $table->string('transaction_id')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
