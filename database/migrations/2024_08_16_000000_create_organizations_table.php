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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name')->unique();
            $table->string('short_name')->unique();
            $table->longText('address')->nullable();
            $table->string('city')->nullable();
            $table->string('town')->nullable();
            $table->string('village')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('max_location_allowed')->default(1);
            $table->integer('max_user_per_location')->default(10);
            $table->string('gstin', 15)->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
