<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Departments — add result_type config + stock management flag
        Schema::table('departments', function (Blueprint $table) {
            $table->string('result_type')->default('none')->after('type');
            $table->boolean('is_stock_managed')->default(false)->after('result_type');
        });

        // 2. Lab Requests — add target_department_id (who handles the request)
        //    existing department_id = requesting/sending department
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->foreignId('target_department_id')
                ->nullable()
                ->after('department_id')
                ->constrained('departments')
                ->nullOnDelete();

            $table->index('target_department_id');
        });

        // 3. Lab Request Items — make lab_test_id nullable
        //    (non-parameter departments don't use the test catalog)
        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->foreignId('lab_test_id')->nullable()->change();
        });

        // 4. Lab Results — make lab_request_item_id nullable (for non-parameter results)
        //    and add result_type + richtext + file fields
        Schema::table('lab_results', function (Blueprint $table) {
            $table->foreignId('lab_request_item_id')->nullable()->change();
            $table->string('result_type')->default('parameters')->after('lab_request_id');
            $table->longText('result_text')->nullable()->after('result_value');
            $table->string('result_file')->nullable()->after('result_text');
            $table->string('result_file_name')->nullable()->after('result_file');
        });
    }

    public function down(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            $table->dropColumn(['result_type', 'result_text', 'result_file', 'result_file_name']);
            $table->foreignId('lab_request_item_id')->nullable(false)->change();
        });

        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->foreignId('lab_test_id')->nullable(false)->change();
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropForeign(['target_department_id']);
            $table->dropIndex(['target_department_id']);
            $table->dropColumn('target_department_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['result_type', 'is_stock_managed']);
        });
    }
};
