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
        Schema::create('company_parameters', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('location_id');

            $table->time('attendance_out_time');
            $table->time('lunch_out_time');

            $table->unsignedInteger('max_day_show')->default(5);

            $table->boolean('status')->default(1);

            $table->timestamps();

            // One parameter record per location
            $table->unique('location_id');

            // Optional Foreign Key
            /*
            $table->foreign('location_id')
                  ->references('id')
                  ->on('locations')
                  ->cascadeOnDelete();
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_parameters');
    }
};