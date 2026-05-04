<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link the lab test catalog to investigation-type departments.
     * - lab_test_categories gets department_id (nullable FK → departments).
     * - lab_tests gets description_template for richtext-type tests.
     */
    public function up(): void
    {
        if (Schema::hasTable('lab_test_categories')) {
            try {
                Schema::table('lab_test_categories', function (Blueprint $table) {
                    $table->foreignId('department_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('departments')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // Column may already exist; ignore.
            }
        }

        if (Schema::hasTable('lab_tests')) {
            try {
                Schema::table('lab_tests', function (Blueprint $table) {
                    $table->text('description_template')->nullable()->after('unit');
                });
            } catch (\Throwable $e) {
                // Column may already exist; ignore.
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('lab_test_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            Schema::table('lab_tests', function (Blueprint $table) {
                $table->dropColumn('description_template');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
