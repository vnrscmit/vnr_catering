<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_details', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('bill_id');

            $table->string('type')->nullable();

            $table->unsignedBigInteger('user_id');

            $table->integer('user_diets')->default(0);

            $table->decimal('rate_per_diet', 10, 2)->default(0);

            $table->decimal('bill_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);

            $table->tinyInteger('status')->default(1);

            $table->timestamps();

            $table->foreign('bill_id')
                ->references('id')
                ->on('bills')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_details');
    }
};
