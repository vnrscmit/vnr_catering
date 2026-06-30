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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();

            $table->enum('guest_type', ['Office Guest', 'Personal Guest']);

            $table->unsignedBigInteger('location_id');
             $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('calendar_id');

            $table->string('guest_name');

            $table->unsignedInteger('guest_count')->default(1);

            $table->text('guest_remarks')->nullable();

            $table->unsignedBigInteger('attend_user_id')->nullable();

            $table->boolean('status')->default(1);

            $table->timestamps();

            // Optional Foreign Keys
            /*
            $table->foreign('calendar_id')
                  ->references('id')
                  ->on('day_statuses')
                  ->cascadeOnDelete();

            $table->foreign('attend_user_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};