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

            $table->decimal('member_rate', 10, 2)->default(0.00);
            $table->decimal('guest_rate', 10, 2)->default(0.00);
            $table->decimal('non_member_rate', 10, 2)->default(0.00);

            $table->time('attendance_out_time');
            $table->time('lunch_out_time');

            $table->unsignedInteger('max_day_show')->default(5);

            $table->tinyInteger('status')->default(1);

            $table->date('active_till_date')->nullable();
            $table->unsignedBigInteger('active_till_calendar_id')->nullable();
              $table->unsignedBigInteger('inactive_user_id')->nullable();

            $table->timestamps();
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
