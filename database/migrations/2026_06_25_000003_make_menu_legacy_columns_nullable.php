<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('menus', 'description')) {
            DB::statement('ALTER TABLE menus MODIFY description TEXT NULL');
        }

        if (DB::getSchemaBuilder()->hasColumn('menus', 'price')) {
            DB::statement('ALTER TABLE menus MODIFY price DECIMAL(10,2) NULL');
        }

        if (DB::getSchemaBuilder()->hasColumn('menus', 'image')) {
            DB::statement('ALTER TABLE menus MODIFY image VARCHAR(255) NULL');
        }

        if (DB::getSchemaBuilder()->hasColumn('menus', 'category_id')) {
            DB::statement('ALTER TABLE menus MODIFY category_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('menus', 'description')) {
            DB::statement('ALTER TABLE menus MODIFY description TEXT NOT NULL');
        }

        if (DB::getSchemaBuilder()->hasColumn('menus', 'price')) {
            DB::statement('ALTER TABLE menus MODIFY price DECIMAL(10,2) NOT NULL');
        }

        if (DB::getSchemaBuilder()->hasColumn('menus', 'image')) {
            DB::statement('ALTER TABLE menus MODIFY image VARCHAR(255) NOT NULL');
        }

        if (DB::getSchemaBuilder()->hasColumn('menus', 'category_id')) {
            DB::statement('ALTER TABLE menus MODIFY category_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
