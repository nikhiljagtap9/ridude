<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('document_type', [
                'driver_license',
                'car_registration',
                'car_insurance',
                'car_inspection',
                'work_id'
            ]);

            $table->string('front_file')
                ->nullable();

            $table->string('back_file')
                ->nullable();

            $table->enum('verification_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->text('rejection_reason')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'user_id',
                'document_type'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
    }
};