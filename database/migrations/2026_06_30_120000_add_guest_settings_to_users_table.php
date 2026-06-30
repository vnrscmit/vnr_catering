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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'personal_guest_flag')) {
                $table->boolean('personal_guest_flag')->default(false)->after('location_id');
            }

            if (!Schema::hasColumn('users', 'max_personal_guest_allowed')) {
                $table->unsignedInteger('max_personal_guest_allowed')->default(0)->after('personal_guest_flag');
            }

            if (!Schema::hasColumn('users', 'max_office_guest_allowed')) {
                $table->unsignedInteger('max_office_guest_allowed')->default(0)->after('max_personal_guest_allowed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'max_office_guest_allowed')) {
                $table->dropColumn('max_office_guest_allowed');
            }

            if (Schema::hasColumn('users', 'max_personal_guest_allowed')) {
                $table->dropColumn('max_personal_guest_allowed');
            }

            if (Schema::hasColumn('users', 'personal_guest_flag')) {
                $table->dropColumn('personal_guest_flag');
            }
        });
    }
};
