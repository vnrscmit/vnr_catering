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
        Schema::create('holiday_lists', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('calendar_id');

            $table->text('remarks')->nullable();

            $table->tinyInteger('status')->default(1);

            $table->timestamps();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->cascadeOnDelete();

            $table->foreign('calendar_id')
                ->references('id')
                ->on('day_statuses')
                ->cascadeOnDelete();

            $table->unique(['location_id', 'calendar_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_lists');
    }
};