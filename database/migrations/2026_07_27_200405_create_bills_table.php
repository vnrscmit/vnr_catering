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

        $table->date('charge_date');

        $table->date('generate_date');

        $table->string('generate_month');

        $table->unsignedBigInteger('calendar_id');

        $table->string('bill_no')->unique();

        $table->date('bill_date');

        $table->integer('total_diets')->default(0);

        $table->integer('individual_set_diet')->default(0);

        $table->integer('president_diet')->default(0);

        $table->integer('guest_diet')->default(0);

        // Non Member Diet
        $table->integer('non_member_diets')->default(0);

        $table->integer('net_chargeable_diet')->default(0);

        $table->integer('total_expenses')->default(0);

        $table->integer('guest_expenses')->default(0);

        // Non Member Charges
        $table->integer('non_member_expenses')->default(0);

        $table->integer('individual_expenses')->default(0);

        $table->integer('net_monthly_expenses')->default(0);

        $table->integer('per_diet_calculation')->default(0);

        $table->decimal('per_diet_calculation_auto', 12, 2)->default(0);

        $table->decimal('balance', 12, 2)->default(0);

        $table->text('remarks')->nullable();

        $table->tinyInteger('status')->default(1);

        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
