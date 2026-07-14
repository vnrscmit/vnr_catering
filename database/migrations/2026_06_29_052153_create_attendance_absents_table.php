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
        Schema::create('attendance_absents', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calendar_id');
            $table->unsignedBigInteger('user_id');

            $table->boolean('absent_flag')->default(1);
            $table->text('absent_remarks')->nullable();

            $table->boolean('override_flag')->default(0);
            $table->text('override_remarks')->nullable();
            $table->unsignedBigInteger('override_user_id')->nullable();
            $table->boolean('late_flag')->default(0);

            $table->boolean('status')->default(1);

            $table->timestamps();



            // Optional Foreign Keys
            /*
            $table->foreign('calendar_id')
                  ->references('id')
                  ->on('day_statuses')
                  ->cascadeOnDelete();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_absents');
    }
};
