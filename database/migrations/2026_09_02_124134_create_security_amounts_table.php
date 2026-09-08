<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_amounts', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 50)->unique();
            $table->date('date');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('status')->default(1); // 1 = Active, 0 = Inactive
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['user_id', 'date']);
            $table->index('transaction_id');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_amounts');
    }
};
