<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_parameter_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_parameter_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('location_id');

            $table->decimal('member_rate', 10, 2)->default(0);
            $table->decimal('non_member_rate', 10, 2)->default(0);
            $table->decimal('guest_rate', 10, 2)->default(0);

            $table->time('attendance_out_time');
            $table->time('lunch_out_time');

            $table->unsignedInteger('max_day_show')->default(5);

            $table->date('active_from_date')->nullable();
            $table->date('active_till_date')->nullable();

            $table->tinyInteger('status')->default(1);

            $table->enum('action', ['Created', 'Updated', 'Inactive']);

            $table->timestamps();

            // Optional Foreign Keys
            // $table->foreign('company_parameter_id')->references('id')->on('company_parameters')->nullOnDelete();
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_parameter_logs');
    }
};