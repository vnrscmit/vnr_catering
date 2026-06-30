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
        Schema::create('day_open', function (Blueprint $table) {
            $table->id();

             $table->unsignedBigInteger('calender_id');
            $table->unsignedBigInteger('location_id');
            $table->date('date');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('opened_by')->nullable();

            $table->timestamps();

            // Prevent duplicate open entry for same location & date
            $table->unique(['location_id', 'date']);

            // Optional Foreign Keys
            /*
            $table->foreign('location_id')
                  ->references('id')
                  ->on('locations')
                  ->cascadeOnDelete();

            $table->foreign('opened_by')
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
        Schema::dropIfExists('day_open');
    }
};