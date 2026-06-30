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
        Schema::create('daily_menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_menu_id');
            $table->unsignedBigInteger('menu_id')->nullable();
            $table->unsignedBigInteger('submenu_id')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_menu_items');
    }
};
