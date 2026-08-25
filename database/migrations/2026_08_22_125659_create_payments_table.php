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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->foreignId('bill_detail_id')->constrained('bill_details')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Amount fields - Using integer for exact amounts like 141, 178
            $table->integer('payable_amount')->default(0);      // 141, 178, etc.
            $table->integer('receive_amount')->default(0);      // 141, 178, etc.
            $table->integer('balance_amount')->default(0);      // 141, 178, etc.
            $table->integer('amount')->default(0);              // 141, 178, etc.

            // Status fields
            $table->enum('status', ['Pending', 'Paid', 'Partial', 'Overdue', 'Cancelled'])->default('Pending');

            // Timestamps
            $table->date('payment_date');
            $table->time('payment_time');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['bill_id', 'user_id']);
            $table->index('status');
            $table->index('payment_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
