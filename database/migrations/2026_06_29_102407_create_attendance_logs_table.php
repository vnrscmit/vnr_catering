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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calendar_id');
            $table->unsignedBigInteger('user_id');

            $table->boolean('absent_flag')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->text('remarks')->nullable();

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

            $table->foreign('created_by')
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
        Schema::dropIfExists('attendance_logs');
    }
};