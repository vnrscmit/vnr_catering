<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guest_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_id'); // Foreign key to guests table
            $table->string('guest_name')->nullable();
            $table->string('department_name')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('guest_details');
    }
};