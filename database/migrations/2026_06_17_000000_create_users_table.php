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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('first_name');
            $table->string('last_name');

            // Contact Information
            $table->string('email')->nullable();
            $table->string('mobile', 10);


            // Professional Information
            $table->string('designation')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();

            // Authentication & Security
            $table->string('password');
            $table->string('activation_token')->nullable();
            $table->boolean('two_factor_auth')->default(false);
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();

            // Guest Settings
            $table->boolean('personal_guest_flag')->default(false);
            $table->integer('max_personal_guest_allowed')->default(0);
            $table->integer('max_office_guest_allowed')->default(0);

            // Status & Additional
            $table->boolean('status')->default(1);
            $table->text('notice')->nullable();
            $table->string('profile_picture')->nullable();

            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('set null');

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('set null');

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('set null');

            // Indexes for better performance
            $table->index('email');
            $table->index('role_id');
            $table->index('personal_guest_flag');
            $table->index('department_id');
            $table->index('location_id');
            $table->index('status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['location_id']);
        });

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
