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
        Schema::create('feast_day_rates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('rate_master_id')->nullable();
            $table->unsignedBigInteger('feast_day_calendar_id');

            $table->date('feast_date');

            $table->decimal('member_rate', 10, 2)->default(0);
            $table->decimal('non_member_rate', 10, 2)->default(0);
            $table->decimal('guest_rate', 10, 2)->default(0);

            $table->tinyInteger('status')->default(1);

            $table->timestamps();

            // Optional Foreign Keys
            $table->foreign('rate_master_id')
                ->references('id')
                ->on('rate_masters')
                ->nullOnDelete();

            $table->foreign('feast_day_calendar_id')
                ->references('id')
                ->on('day_statuses')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feast_day_rates');
    }
};
