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
        Schema::create('rate_masters', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('location_id');
            $table->date('effective_from_date');
            $table->date('effective_to_date')->nullable();

            $table->unsignedBigInteger('effective_from_calendar_id');
            $table->unsignedBigInteger('effective_to_calendar_id')->nullable();

            $table->decimal('member_rate', 10, 2)->default(0.00);
            $table->decimal('non_member_rate', 10, 2)->default(0.00);
            $table->decimal('guest_rate', 10, 2)->default(0.00);
            $table->decimal('min_day_rate', 10, 2)->default(0.00);

            $table->enum('type', ['Monthly', 'Fixed'])->default('Monthly');

            $table->unsignedBigInteger('created_by');

            $table->tinyInteger('status')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_masters');
    }
};
