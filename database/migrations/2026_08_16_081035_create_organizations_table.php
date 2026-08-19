<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('namorganization_namee');
            $table->string('short_name');
            $table->text('address');
            
            // New location fields
            $table->string('state', 100);
            $table->string('district', 100);
            $table->string('tehsil', 100)->nullable();
            $table->string('city_village', 100);
            
            $table->string('pincode', 10);
            $table->string('logo')->nullable();
            $table->boolean('status')->default(1);
            $table->integer('max_location_allowed')->default(1);
            $table->integer('max_user_per_location')->default(10);
            $table->string('gstin')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};