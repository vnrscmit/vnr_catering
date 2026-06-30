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
        Schema::create('day_statuses', function (Blueprint $table) {
            $table->id();

            $table->date('date');
            $table->string('day_name', 20);
            $table->string('month', 20);
            $table->unsignedTinyInteger('day');
            $table->year('year');

            $table->boolean('holiday_flag')->default(0);
            $table->boolean('sunday_flag')->default(0);

            $table->boolean('open_flag')->default(1);
            $table->text('open_remarks')->nullable();
            $table->unsignedBigInteger('open_user_id')->nullable();

            $table->boolean('closed_flag')->default(0);
            $table->text('closed_remarks')->nullable();

            $table->text('remarks')->nullable();

            $table->boolean('status')->default(1);

            $table->timestamps();

            // Optional Foreign Key
            // $table->foreign('open_user_id')
            //       ->references('id')
            //       ->on('users')
            //       ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_statuses');
    }
};
