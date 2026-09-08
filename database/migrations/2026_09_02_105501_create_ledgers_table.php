<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('calendar_id')->nullable();
             $table->unsignedBigInteger('bill_id')->nullable();
            $table->date('date');
            $table->text('transaction');
            $table->integer('due')->default(0);
            $table->integer('paid')->default(0);
            $table->integer('balance')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('calendar_id')->references('id')->on('day_statuses')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['user_id', 'date']);
            $table->index('calendar_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
