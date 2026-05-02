<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $exists = count(DB::select("SHOW COLUMNS FROM `lab_results` LIKE 'result_type'")) > 0;
        if ($exists) {
            return; // already exists
        }
        Schema::table('lab_results', function (Blueprint $table) {
            // Make result_value nullable — richtext/file results don't use it
            $table->text('result_value')->nullable()->change();

            // New columns for multi-type result support
            $table->string('result_type')->default('parameters')->after('lab_request_id');
            $table->longText('result_text')->nullable()->after('result_value');
            $table->string('result_file')->nullable()->after('result_text');
            $table->string('result_file_name')->nullable()->after('result_file');
        });
    }

    public function down(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            $table->text('result_value')->nullable(false)->change();
            $table->dropColumn(['result_type', 'result_text', 'result_file', 'result_file_name']);
        });
    }
};
