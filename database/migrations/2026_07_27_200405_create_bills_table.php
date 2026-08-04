<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {

            $table->id();

            $table->string('type')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();

            $table->date('generate_date');

            $table->string('generate_month');

            $table->unsignedBigInteger('calendar_id');

            $table->string('bill_no')->unique();

            $table->date('bill_date');

            $table->integer('total_diets')->default(0);

            $table->integer('individual_set_diet')->default(0);

            $table->integer('president_diet')->default(0);

            $table->integer('guest_diet')->default(0);

            $table->integer('net_chargeable_diet')->default(0);

            $table->decimal('total_expenses',12,2)->default(0);

            $table->decimal('guest_expenses',12,2)->default(0);

            $table->decimal('individual_expenses',12,2)->default(0);

            $table->decimal('net_monthly_expenses',12,2)->default(0);

            $table->decimal('per_diet_calculation',12,2)->default(0);

            $table->tinyInteger('status')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
